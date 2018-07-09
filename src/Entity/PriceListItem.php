<?php

namespace Drupal\commerce_pricelist\Entity;

use Drupal\commerce_price\Price;
use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the price list item entity.
 *
 * @ingroup commerce_pricelist
 *
 * @ContentEntityType(
 *   id = "commerce_price_list_item",
 *   label = @Translation("Price list item"),
 *   label_collection = @Translation("Price list items"),
 *   label_singular = @Translation("price list item"),
 *   label_plural = @Translation("price list items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count price list item",
 *     plural = "@count price list items",
 *   ),
 *   bundle_label = @Translation("price list item type"),
 *   handlers = {
 *     "access" = "Drupal\commerce\EmbeddedEntityAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_pricelist\PriceListItemListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_pricelist\PriceListItemRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_price_list",
 *   base_table = "price_list_item",
 *   data_table = "price_list_item_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *   },
 *   links = {
 *     "add-form" = "/pricelist/{commerce_price_list}/prices/add",
 *     "edit-form" = "/pricelist/{commerce_price_list}/prices/{commerce_price_list_item}/edit",
 *     "delete-form" = "/pricelist/{commerce_price_list}/prices/{commerce_price_list_item}/delete",
 *     "collection" = "/pricelist/{commerce_price_list}/prices",
 *   },
 * )
 */
class PriceListItem extends CommerceContentEntityBase implements PriceListItemInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['commerce_price_list'] = $this->getPriceListId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceList() {
    return $this->get('price_list_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setPriceList(PriceListInterface $price_list) {
    return $this->set('price_list_id', $price_list);
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceListId() {
    return $this->get('price_list_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setPriceListId($price_list_id) {
    return $this->set('price_list_id', $price_list_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return $this->get('quantity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuantity($quantity) {
    $this->set('quantity', (string) $quantity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrice(Price $price) {
    return $this->set('price', $price);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    if (!$this->get('price')->isEmpty()) {
      return $this->get('price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setListPrice(Price $price) {
    return $this->set('list_price', $price);
  }

  /**
   * {@inheritdoc}
   */
  public function getListPrice() {
    if (!$this->get('list_price')->isEmpty()) {
      return $this->get('list_price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasPurchasedEntity() {
    return !$this->get('purchased_entity')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedEntity() {
    return $this->getTranslatedReferencedEntity('purchased_entity');
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedEntityId() {
    return $this->get('purchased_entity')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchasedEntityId($purchased_entity_id) {
    return $this->set('purchased_entity', $purchased_entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    $fields['status']
      ->setLabel(t('Activated'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 120,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The price list item author.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_pricelist\Entity\PriceListItem::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The Weight of the Price list item entity.'))
      ->setDefaultValue(0);

    $fields['price_list_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Price list'))
      ->setDescription(t('The parent price list of the Price list item entity.'))
      ->setSetting('target_type', 'commerce_price_list')
      ->setReadOnly(TRUE);

    $fields['purchased_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Purchased entity'))
      ->setDescription(t('The purchased entity of the price list item.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Optional label for this price list item.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The product quantity number of the Price list item entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue(1)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'integer',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'integer',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Price'))
      ->setDescription(t('The price of the Price list item entity.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['list_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('List price'))
      ->setDescription(t('The list price.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the price list item was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = [];

    $fields['price_list_id'] = clone $base_field_definitions['price_list_id'];
    $fields['price_list_id']->setSetting('handler_settings', ['target_bundles' => [$bundle => $bundle]]);

    $purchased_entity_type = \Drupal::entityTypeManager()->getDefinition($bundle);
    $fields['purchased_entity'] = clone $base_field_definitions['purchased_entity'];
    $fields['purchased_entity']->setSetting('target_type', $purchased_entity_type->id());
    $fields['purchased_entity']->setLabel($purchased_entity_type->getLabel());

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
