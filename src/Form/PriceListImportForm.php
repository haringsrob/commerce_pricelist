<?php

namespace Drupal\commerce_pricelist\Form;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_pricelist\CSVFileObject;
use Drupal\commerce_pricelist\Entity\PriceListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Import form for a price list's items.
 */
class PriceListImportForm extends FormBase {

  /**
   * The number of price list items to generate in each batch.
   *
   * @var int
   */
  const BATCH_SIZE = 25;

  const STRATEGY_UPDATE_EXISTING = 'update_existing';

  const STRATEGY_SKIP_EXISTING = 'skip_existing';

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

    $form['#attached']['library'][] = 'commerce_pricelist/form';

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

    $form['purchasable_entity_mapping'] = [
      '#type' => 'fieldset',
      '#title' => 'Product mapping',
      '#attributes' => [
        'class' => ['inline'],
      ],
      '#tree' => TRUE,
    ];
    $form['purchasable_entity_mapping']['field'] = [
      '#type' => 'select',
      '#title' => 'Field',
      '#required' => TRUE,
      '#options' => array_map(function (FieldDefinitionInterface $field_definition) {
        return $field_definition->getLabel();
      }, $purchasable_entity_type_mapping_fields),
      '#default_value' => isset($purchasable_entity_type_mapping_fields['sku']) ? 'sku' : $target_purchasable_entity_type->getKey('uuid'),
    ];
    $form['purchasable_entity_mapping']['column'] = [
      '#type' => 'textfield',
      '#title' => 'CSV column name',
      '#required' => TRUE,
    ];

    $form['fields'] = [
      '#type' => 'fieldset',
      '#title' => 'Fields',
    ];
    $form['fields']['sell_price_csv_column'] = [
      '#type' => 'textfield',
      '#title' => 'Sell Price CSV column name',
      '#required' => TRUE,
    ];
    $form['fields']['list_price_csv_column'] = [
      '#type' => 'textfield',
      '#title' => 'List price CSV column name',
      '#description' => $this->t('If left empty, no list price will be set.'),
    ];
    $form['fields']['quantity_csv_column'] = [
      '#type' => 'textfield',
      '#title' => 'Quantity CSV column name',
      '#description' => $this->t('If left empty, quantity will default to 1.'),
    ];

    // @todo Should we show a confirm form if this is selected?
    $form['purge'] = [
      '#type' => 'checkbox',
      '#title' => 'Delete all items in this price list prior to import.',
    ];

    $form['strategy'] = [
      '#type' => 'radios',
      '#title' => 'Import strategy',
      '#options' => [
        self::STRATEGY_UPDATE_EXISTING => 'Update price list items for existing products in the import.',
        self::STRATEGY_SKIP_EXISTING => 'Ignore existing price list items for existing products in the import.',
      ],
      '#default_value' => self::STRATEGY_UPDATE_EXISTING,
      '#states' => [
        'visible' => [
          'input[name="purge"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // @todo Perhaps we should always show a confirm form showing the settings.
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
      'mapping' => $values['purchasable_entity_mapping']['column'],
      'sell_price' => $values['sell_price_csv_column'],
      'list_price' => $values['list_price_csv_column'],
      'quantity' => $values['quantity_csv_column'],
    ]);

    $batch = [
      'title' => $this->t('Importing price list items'),
      'progress_message' => '',
      'operations' => [],
      'finished' => [$this, 'finishBatch'],
    ];

    if ($values['purge']) {
      $batch['operations'][] = [
        [get_class($this), 'batchPurgeExisting'],
        [$form_state->get('price_list_id')],
      ];
    }
    $batch['operations'][] = [
      [get_class($this), 'batchProcess'],
      [
        $file->getFileUri(),
        $values['purchasable_entity_mapping']['field'],
        $columns,
        $values['strategy'],
        $form_state->get('price_list_id'),
      ],
    ];
    $batch['operations'][] = [
      [get_class($this), 'batchDeleteUploadedFile'],
      [$file->getFileUri()],
    ];


    batch_set($batch);
    $form_state->setRedirect('entity.commerce_price_list.edit_form', [
      'commerce_price_list' => $form_state->get('price_list_id'),
    ]);
  }

  /**
   * Batch operation to purge existing items on the price list.
   *
   * @param int $price_list_id
   *   The price list ID.
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function batchPurgeExisting($price_list_id, array &$context) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $price_list_storage = $entity_type_manager->getStorage('commerce_price_list');
    $price_list_item_storage = $entity_type_manager->getStorage('commerce_price_list_item');

    /** @var \Drupal\commerce_pricelist\Entity\PriceList $price_list */
    $price_list = $price_list_storage->load($price_list_id);

    if (empty($context['sandbox'])) {
      $context['sandbox']['total_items'] = $price_list->get('items')->count();
      $context['sandbox']['deleted'] = 0;
      $context['results']['total_items'] = $context['sandbox']['total_items'];
    }

    $total_items = $context['sandbox']['total_items'];
    $deleted = &$context['sandbox']['deleted'];
    $remaining = $total_items - $deleted;
    $limit = (int) ($remaining < self::BATCH_SIZE) ? $remaining : self::BATCH_SIZE;

    if ($total_items == 0 || $price_list->get('items')->isEmpty()) {
      $context['finished'] = 1;
    }
    else {
      $price_list_item_ids = array_slice($price_list->getItemsIds(), 0, $limit);
      $price_list_items = $price_list_item_storage->loadMultiple($price_list_item_ids);
      $price_list_item_storage->delete($price_list_items);

      // The normal filter on empty items does not work, because entity
      // reference only cares if the target_id is set, not that it is a viable
      // reference. This is only checked on the constraints. But constraints
      // do not provide enough data. So we use a custom filter.
      $price_list->get('items')->filter(function (EntityReferenceItem $item) use ($price_list_item_ids) {
        return !in_array($item->target_id, $price_list_item_ids);
      });
      $price_list->save();

      $deleted = $deleted + $limit;

      $context['message'] = t('Deleting price list item @deleted of @total_items', [
        '@deleted' => $deleted,
        '@total_items' => $total_items,
      ]);
      $context['finished'] = $deleted / $total_items;
    }
  }

  /**
   * Batch process to import price list items from the CSV.
   *
   * @param string $file_uri
   *   The CSV file URI.
   * @param string $mapping_field
   *   The purchasable entity mapping field.
   * @param array $columns
   *   The CSV columns.
   * @param string $strategy
   *   The existing price list item strategy.
   * @param string $price_list_id
   *   The price list ID.
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function batchProcess($file_uri, $mapping_field, array $columns, $strategy, $price_list_id, array &$context) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $price_list_storage = $entity_type_manager->getStorage('commerce_price_list');
    $price_list_item_storage = $entity_type_manager->getStorage('commerce_price_list_item');
    /** @var \Drupal\commerce_pricelist\Entity\PriceList $price_list */
    $price_list = $price_list_storage->load($price_list_id);

    $purchasable_entity_storage = $entity_type_manager->getStorage($price_list->bundle());

    $csv = new CSVFileObject($file_uri);
    $csv->setColumnNames($columns);

    if (empty($context['sandbox'])) {
      $context['sandbox']['import_total'] = (int) $csv->count();
      $context['sandbox']['created'] = 0;
      $context['results']['import_total'] = $context['sandbox']['import_total'];
      $context['results']['import_skipped'] = [];
      $context['results']['import_updated'] = [];
      $csv->rewind();
    }

    $import_total = $context['sandbox']['import_total'];
    $created = &$context['sandbox']['created'];
    $remaining = $import_total - $created;
    $limit = ($remaining < self::BATCH_SIZE) ? $remaining : self::BATCH_SIZE;

    $csv->seek($created + $csv->getHeaderRowCount());
    if ($csv->valid()) {
      /** @var \Drupal\commerce_pricelist\Entity\PriceList $price_list */
      $default_currency = $price_list->getStore()->getDefaultCurrency();

      $mapping_field_column = $columns['mapping'];
      for ($i = 0; $i < $limit; $i++) {
        if (!$csv->valid()) {
          break;
        }
        $current = $csv->current();

        $purchasable_entity = $purchasable_entity_storage->loadByProperties([
          $mapping_field => $current[$mapping_field_column],
        ]);
        $purchasable_entity = reset($purchasable_entity);

        // Bail early if the mapped purchasable entity value is invalid.
        if (!$purchasable_entity instanceof PurchasableEntityInterface) {
          $context['results']['import_skipped'][] = $current[$mapping_field];
          $created++;
          $csv->next();
          continue;
        }

        // Check if there is an existing price list item in this price list
        // which targets the same purchasable entity.
        $count = $price_list_item_storage->getQuery()
          ->condition('price_list_id', $price_list->id())
          ->condition('purchased_entity', $purchasable_entity->id())
          ->count()
          ->execute();

        if (!empty($columns['quantity'])) {
          $quantity = $current[$columns['quantity']];
        }
        else {
          $quantity = 1;
        }

        if ($count == 0) {
          $item = $price_list_item_storage->create([
            'type' => $price_list->bundle(),
            'uid' => $price_list->getOwnerId(),
            'price_list_id' => $price_list->id(),
            'purchased_entity' => $purchasable_entity->id(),
            'name' => $current[$mapping_field_column],
            'quantity' => $quantity,
            'price' => new Price($current[$columns['sell_price']], $default_currency->getCurrencyCode()),
          ]);
          $item->save();
          $price_list->get('items')->appendItem($item);
        }
        elseif ($strategy == self::STRATEGY_UPDATE_EXISTING) {
          $existing_price_item = $price_list_item_storage->loadByProperties([
            'price_list_id' => $price_list->id(),
            'purchased_entity' => $purchasable_entity->id(),
          ]);
          /** @var \Drupal\commerce_pricelist\Entity\PriceListItemInterface $existing_price_item */
          $existing_price_item = reset($existing_price_item);
          $existing_price_item->setPrice(new Price($current[$columns['sell_price']], $default_currency->getCurrencyCode()));
          $existing_price_item->setQuantity($quantity);
          $existing_price_item->save();
          $context['results']['import_updated'][] = $current[$mapping_field_column];
        }
        else {
          // Skip.
          $context['results']['import_skipped'][] = $current[$mapping_field_column];
        }

        $created++;
        $csv->next();
      }
      $price_list->save();
      $context['message'] = t('Importing @created of @import_total price list items', [
        '@created' => $created,
        '@import_total' => $import_total,
      ]);
      $context['finished'] = $created / $import_total;
    }
    else {
      $context['finished'] = 1;
    }

  }

  /**
   * Batch process to delete the uploaded CSV.
   *
   * @param string $file_uri
   *   The CSV file URI.
   * @param array $context
   *   The batch context.
   */
  public static function batchDeleteUploadedFile($file_uri, array &$context) {
    file_unmanaged_delete($file_uri);
    $context['message'] = t('Removing uploaded CSV.');
    $context['finished'] = 1;
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
        $results['import_total'],
        'Imported 1 price list item. You may now review them.',
        'Imported @count price list items. You may now review them.'
      ));
      if (!empty($results['import_updated'])) {
        \Drupal::messenger()->addMessage(\Drupal::translation()->formatPlural(
          count($results['import_updated']),
          'Updated 1 price list item during import.',
          'Updated @count price list items during import.'
        ));
      }
      if (!empty($results['import_skipped'])) {
        \Drupal::messenger()->addWarning(\Drupal::translation()->formatPlural(
          count($results['import_skipped']),
          'Skipped 1 price list item during import.',
          'Skipped @count price list items during import.'
        ));
      }
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
