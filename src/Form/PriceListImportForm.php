<?php

namespace Drupal\commerce_pricelist\Form;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_pricelist\CSVFileObject;
use Drupal\commerce_pricelist\Entity\PriceList;
use Drupal\commerce_pricelist\Entity\PriceListInterface;
use Drupal\commerce_pricelist\Entity\PriceListItemInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolation;

class PriceListImportForm extends FormBase {

  /**
   * The number of price list items to generate in each batch.
   *
   * @var int
   */
  const BATCH_SIZE = 25;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pricelist_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PriceListInterface $commerce_price_list = NULL) {
    $form_state->set('price_list_id', $commerce_price_list->id());
    $entity_type_manager = \Drupal::entityTypeManager();
    $target_purchasable_entity_type = $entity_type_manager->getDefinition($commerce_price_list->bundle());
    $mappable_field_names = [
      $target_purchasable_entity_type->getKey('id'),
      $target_purchasable_entity_type->getKey('uuid'),
      $target_purchasable_entity_type->getKey('label'),
      // Product variation specific.
      'sku',
    ];
    $base_field_definitions = \Drupal::getContainer()->get('entity_field.manager')->getBaseFieldDefinitions($commerce_price_list->bundle());
    $purchasable_entity_type_mapping_fields = array_filter($base_field_definitions, function (FieldDefinitionInterface $field_definition) use ($mappable_field_names) {
      return in_array($field_definition->getName(), $mappable_field_names);
    });

    $validators = [
      'file_validate_extensions' => ['csv'],
      'file_validate_size' => [file_upload_max_size()],
    ];
    $form['csv'] = [
      '#type' => 'file',
      '#title' => t('Choose a file'),
      '#upload_validators' => $validators,
      '#upload_location' => 'temporary://',
    ];

    $form['mapping'] = [
      '#type' => 'fieldset',
      '#title' => 'Mapping',
      '#tree' => FALSE,
    ];
    $form['mapping']['mappable_field'] = [
      '#type' => 'select',
      '#title' => 'Field',
      '#options' => array_map(function (FieldDefinitionInterface $field_definition) {
        return $field_definition->getLabel();
      }, $purchasable_entity_type_mapping_fields),
      '#default_value' => isset($purchasable_entity_type_mapping_fields['sku']) ? 'sku' : $target_purchasable_entity_type->getKey('uuid'),
    ];
    $form['mapping']['import_field'] = [
      '#type' => 'textfield',
      '#title' => 'CSV column name',
    ];

    $form['pricing'] = [
      '#type' => 'fieldset',
      '#title' => 'Prices',
      '#tree' => FALSE,
    ];
    $form['pricing']['sell_price_csv_column'] = [
      '#type' => 'textfield',
      '#title' => 'Sell Price CSV column name',
    ];
    $form['pricing']['list_price_csv_column'] = [
      '#type' => 'textfield',
      '#title' => 'List price CSV column name',
    ];

    $form['purge'] = [
      '#type' => 'checkbox',
      '#title' => 'Delete all items in this price list prior to import.',
    ];

    $form['strategy'] = [
      '#type' => 'radios',
      '#title' => 'Import strategy',
      '#options' => [
        'update_exists' => 'Update price list items for existing products in the import.',
        'skip_exists' => 'Ignore existing price list items for existing products in the import.',
      ],
      '#default_value' => 'update_exists',
      '#states' => [
        'visible' => [
          'input[name="purge"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import price list items'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile[] $all_files */
    $all_files = $this->getRequest()->files->get('files', []);
    if (empty($all_files['csv'])) {
      $form_state->setErrorByName('csv', t('Missing CSV'));
    }
    elseif (!$all_files['csv']->isValid()) {
      $form_state->setErrorByName('csv', t('Bad CSV'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file = file_save_upload('csv', $form['csv']['#upload_validators'], 'temporary://', 0, FILE_EXISTS_RENAME);
    $values = $form_state->getValues();

    $columns = array_filter([
      $values['import_field'],
      $values['sell_price_csv_column'],
      $values['list_price_csv_column'],
    ]);

    $batch = [
      'title' => $this->t('Importing price list items'),
      'progress_message' => '',
      'operations' => [],
      'finished' => [$this, 'finishBatch'],
    ];

    if ($values['purge']) {
      $batch['operations'][] = [
        [get_class($this), 'purgeExisting'],
        [$form_state->get('price_list_id')],
      ];
    }
    $batch['operations'][] = [
      [get_class($this), 'processBatch'],
      [$file->getFileUri(), $columns, $values['strategy'], $form_state->get('price_list_id')],
    ];
    $batch['operations'][] = [
      [get_class($this), 'deleteUploadedFile'],
      [$file->getFileUri()],
    ];


    batch_set($batch);
    $form_state->setRedirect('entity.commerce_price_list.edit_form', [
      'commerce_price_list' => $form_state->get('price_list_id'),
    ]);
  }

  public static function purgeExisting($price_list_id, array &$context) {
    /** @var \Drupal\commerce_pricelist\Entity\PriceList $price_list */
    $price_list = PriceList::load($price_list_id);
    if (empty($context['sandbox'])) {
      $context['sandbox']['total_quantity'] = $price_list->get('items')->count();
      $context['sandbox']['deleted'] = 0;
      $context['results']['total_quantity'] = $context['sandbox']['total_quantity'];
    }
    $total_quantity = $context['sandbox']['total_quantity'];
    $deleted = &$context['sandbox']['deleted'];
    $remaining = $total_quantity - $deleted;
    $limit = (int) ($remaining < self::BATCH_SIZE) ? $remaining : self::BATCH_SIZE;

    if ($total_quantity == 0 || $price_list->get('items')->isEmpty()) {
      $context['finished'] = 1;
    }
    else {
      $entity_type_manager = \Drupal::entityTypeManager();
      $price_list_item_storage = $entity_type_manager->getStorage('commerce_price_list_item');

      $price_list_item_ids = array_slice($price_list->getItemsIds(), 0, $limit);
      $price_list_items = $price_list_item_storage->loadMultiple($price_list_item_ids);
      $price_list_item_storage->delete($price_list_items);

      // The normal filter on empty items does not work, because entity
      // reference only cares if the target_id is set, not that it is a viable
      // reference. This is only checked on the constraints. But constraints
      // do not provide enough data. So we use a custom filter.
      $price_list->get('items')->filter(function (EntityReferenceItem $item) {
        return $item->entity !== NULL;
      });
      $price_list->save();

      $deleted = $deleted + $limit;

      $context['message'] = t('Deleting price list item @deleted of @total_quantity', [
        '@deleted' => $deleted,
        '@total_quantity' => $total_quantity,
      ]);
      $context['finished'] = $deleted / $total_quantity;
    }
  }

  /**
   * Processes the batch and imports the price list items.
   *
   * @param $file_uri
   * @param array $columns
   * @param $strategy
   * @param array $context
   *   The batch context information.
   */
  public static function processBatch($file_uri, array $columns, $strategy, $price_list_id, array &$context) {
    $csv = new CSVFileObject($file_uri);
    $csv->setColumnNames($columns);

    if (empty($context['sandbox'])) {
      $context['sandbox']['total_quantity'] = (int) $csv->count();
      $context['sandbox']['created'] = 0;
      $context['results']['total_quantity'] = $context['sandbox']['total_quantity'];
      $csv->rewind();
    }

    $total_quantity = $context['sandbox']['total_quantity'];
    $created = &$context['sandbox']['created'];
    $remaining = $total_quantity - $created;
    $limit = ($remaining < self::BATCH_SIZE) ? $remaining : self::BATCH_SIZE;

    $csv->seek($created + $csv->getHeaderRowCount());
    if ($csv->valid()) {
      $entity_type_manager = \Drupal::entityTypeManager();
      /** @var \Drupal\commerce_pricelist\Entity\PriceList $price_list */
      $price_list = PriceList::load($price_list_id);
      $default_currency = $price_list->getStore()->getDefaultCurrency();

      $purchasable_entity_storage = $entity_type_manager->getStorage($price_list->bundle());
      $price_list_item_storage = $entity_type_manager->getStorage('commerce_price_list_item');

      $mapping_field = reset($columns);

      for ($i = 0; $i < $limit; $i++) {
        if (!$csv->valid()) {
          break;
        }
        $current = $csv->current();

        $purchasable_entity = $purchasable_entity_storage->loadByProperties([
          $mapping_field => $current[$mapping_field],
        ]);
        $purchasable_entity = reset($purchasable_entity);

        if (!$purchasable_entity instanceof PurchasableEntityInterface) {
          $context['skipped'][] = $current[$mapping_field];
          continue;
        }

        $item = $price_list_item_storage->create([
          'type' => $price_list->bundle(),
          'uid' => $price_list->getOwnerId(),
          'price_list_id' => $price_list->id(),
          'purchased_entity' => $purchasable_entity->id(),
          'name' => $purchasable_entity->label(),
          // @todo support quantity in the CSV.
          'quantity' => 1,
          'price' => new Price($current[$columns[1]], $default_currency->getCurrencyCode()),
        ]);
        $item->save();
        $price_list->get('items')->appendItem($item);

        $created++;
        $csv->next();
      }
      $price_list->save();
      $context['message'] = t('Importing @created of @total_quantity price list items', [
        '@created' => $created,
        '@total_quantity' => $total_quantity,
      ]);
      $context['finished'] = $created / $total_quantity;
    }
    else {
      $context['finished'] = 1;
    }

  }

  public static function deleteUploadedFile($file_uri, array &$context) {
    $result = file_unmanaged_delete($file_uri);
  }

  /**
   * Batch finished callback: display batch statistics.
   *
   * @param bool $success
   *   Indicates whether the batch has completed successfully.
   * @param mixed[] $results
   *   The array of results gathered by the batch processing.
   * @param string[] $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function finishBatch($success, array $results, array $operations) {
    if ($success) {
      \Drupal::messenger()->addMessage(\Drupal::translation()->formatPlural(
        $results['total_quantity'],
        'Imported 1 price list item. You may now review them.',
        'Importeed @count price list items. You may now review them.'
      ));
    }
    else {
      $error_operation = reset($operations);
      \Drupal::messenger()->addError(t('An error occurred while processing @operation with arguments: @args', [
        '@operation' => $error_operation[0],
        '@args' => (string) print_r($error_operation[0], TRUE),
      ]));
    }
  }

}
