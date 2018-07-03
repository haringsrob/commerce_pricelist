<?php

namespace Drupal\commerce_pricelist\Form;

use Drupal\commerce_pricelist\Entity\PriceListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PriceListImportForm extends FormBase {

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

    $form['mappable_row'] = [
      '#type' => 'fieldset',
      '#title' => 'Mapping',
      '#tree' => FALSE,
    ];
    $form['mappable_row']['mappable_field'] = [
      '#type' => 'select',
      '#title' => 'Field',
      '#options' => array_map(function (FieldDefinitionInterface $field_definition) {
        return $field_definition->getLabel();
      }, $purchasable_entity_type_mapping_fields),
      '#default_value' => isset($purchasable_entity_type_mapping_fields['sku']) ? 'sku' : $target_purchasable_entity_type->getKey('uuid'),
    ];
    $form['mappable_row']['import_field'] = [
      '#type' => 'textfield',
      '#title' => 'CSV column name',
    ];

    $form['pricing_row'] = [
      '#type' => 'fieldset',
      '#title' => 'Prices',
      '#tree' => FALSE,
    ];
    $form['pricing_row']['price_csv_column'] = [
      '#type' => 'textfield',
      '#title' => 'Price CSV column name',
    ];
    $form['pricing_row']['list_price_csv_column'] = [
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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

}
