<?php

namespace Drupal\commerce_pricelist\Entity;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Price list entity.
 *
 * @ingroup commerce_pricelist
 *
 * @ContentEntityType(
 *   id = "commerce_price_list",
 *   label = @Translation("Price list"),
 *   label_collection = @Translation("Price lists"),
 *   label_singular = @Translation("price list"),
 *   label_plural = @Translation("price lists"),
 *   label_count = @PluralTranslation(
 *     singular = "@count price list",
 *     plural = "@count price lists",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_pricelist\PriceListListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "\Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "\Drupal\entity\EntityPermissionProvider",
 *     "form" = {
 *       "default" = "Drupal\commerce_pricelist\Form\PriceListForm",
 *       "add" = "Drupal\commerce_pricelist\Form\PriceListForm",
 *       "edit" = "Drupal\commerce_pricelist\Form\PriceListForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\AdminHtmlRouteProvider",
 *       "delete-multiple" =
 *   "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_price_list",
 *   base_table = "price_list",
 *   data_table = "price_list_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/price_list/{price_list}",
 *     "add-page" = "/price_list/add",
 *     "add-form" = "/price_list/add/{price_list_type}",
 *     "edit-form" = "/price_list/{price_list}/edit",
 *     "delete-form" = "/price_list/{price_list}/delete",
 *     "delete-multiple-form" = "/admin/commerce/price_list/delete",
 *     "collection" = "/admin/commerce/price_lists",
 *   },
 * )
 */
class PriceList extends CommerceContentEntityBase implements PriceListInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

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
  public function getOwner() {
    return $this->get('uid')->entity;
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
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    // Can't use the ->date property because it resets the timezone to UTC.
    return new DrupalDateTime($this->get('start_date')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setStartDate(DrupalDateTime $start_date) {
    $this->get('start_date')->value = $start_date->format('Y-m-d');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    if (!$this->get('end_date')->isEmpty()) {
      return new DrupalDateTime($this->get('end_date')->value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setEndDate(DrupalDateTime $end_date = NULL) {
    $this->get('end_date')->value = $end_date ? $end_date->format('Y-m-d') : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStore() {
    return $this->getTranslatedReferencedEntity('store_id');
  }

  /**
   * {@inheritdoc}
   */
  public function setStore(StoreInterface $store) {
    $this->set('store_id', $store->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreId() {
    return $this->get('store_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreId($store_id) {
    $this->set('store_id', $store_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsIds() {
    $price_list_item_ids = [];
    foreach ($this->get('items') as $field_item) {
      $price_list_item_ids[] = $field_item->target_id;
    }
    return $price_list_item_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    $items = $this->get('items')->referencedEntities();
    return $items;
  }

  public function getTargetUserId() {
    return $this->get('target_uid')->target_id;
  }

  public function getTargetUser() {
    return $this->get('target_uid')->entity;
  }

  public function setTargetUserId($uid) {
    $this->set('target_uid', $uid);
    return $this;
  }

  public function setTargetUser(UserInterface $user) {
    $this->set('target_uid', $user->id());
    return $this;
  }

  public function getTargetRoleId() {
    return $this->get('target_role')->target_id;
  }

  public function getTargetRole() {
    return $this->get('target_role')->entity;
  }

  public function setTargetRoleId($rid) {
    $this->set('target_role', $rid);
    return $this;
  }

  public function setTargetRole(RoleInterface $role) {
    $this->set('target_role', $role->id());
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a back-reference on each price list item.
    foreach ($this->items as $item) {
      $price_list_item = $item->entity;
      if ($price_list_item->price_list_id->isEmpty()) {
        $price_list_item->price_list_id = $this->id();
        $price_list_item->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete the price list item of a deleted price list.
    /** @var \Drupal\commerce_pricelist\Entity\PriceListInterface $entity */
    foreach ($entities as $entity) {
      $price_list_items = [];
      if ($entity->get('items')->isEmpty()) {
        continue;
      }
      foreach ($entity->items as $item) {
        $price_list_items[$item->target_id] = $item->entity;
      }

      $price_list_item_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_price_list_item');
      $price_list_item_storage->delete($price_list_items);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this pricelist in relation to other price lists.'))
      ->setDefaultValue(0);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Price list entity.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setDescription(t('The user ID of author of the price list entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('\Drupal\commerce_pricelist\Entity\PriceList::getCurrentUserId')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date'))
      ->setDescription(t('The date the price list becomes valid.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDefaultValueCallback('Drupal\commerce_pricelist\Entity\PriceList::getDefaultStartDate')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 5,
      ]);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date'))
      ->setDescription(t('The date after which the price list is invalid.'))
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'commerce_end_date',
        'weight' => 6,
      ]);

    $fields['items'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('List items'))
      ->setDescription(t('List items with prices.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(FALSE)
      ->setSetting('target_type', 'commerce_price_list_item')
      ->setSetting('handler', 'default');

    $fields['store_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store'))
      ->setDescription(t('The store to which the order belongs.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['target_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user the price list is for.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['target_role'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Role'))
      ->setDescription(t('The role the price list is for.'))
      ->setSetting('target_type', 'user_role')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = [];
    $fields['items'] = clone $base_field_definitions['items'];
    $fields['items']->setSetting('target_type', $bundle);
    return $fields;
  }

  /**
   * Default value callback for 'start_date' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return string
   *   The default value (date string).
   */
  public static function getDefaultStartDate() {
    $timestamp = \Drupal::time()->getRequestTime();
    return gmdate('Y-m-d', $timestamp);
  }

  /**
   * Default value callback for 'end_date' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return int
   *   The default value (date string).
   */
  public static function getDefaultEndDate() {
    // Today + 1 year.
    $timestamp = \Drupal::time()->getRequestTime();
    return gmdate('Y-m-d', $timestamp + 31536000);
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
