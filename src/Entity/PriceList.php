<?php

namespace Drupal\commerce_pricelist\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
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
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer price_list",
 *   base_table = "price_list",
 *   data_table = "price_list_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
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
  public function getType() {
    return $this->bundle();
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
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
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
  public function getItemsIds() {
    $price_list_item_ids = [];
    foreach ($this->get('field_price_list_item') as $field_item) {
      $price_list_item_ids[] = $field_item->target_id;
    }
    return $price_list_item_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->getTranslatedReferencedEntities('field_price_list_item');
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a back-reference on each price list item.
    foreach ($this->field_price_list_item as $item) {
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
    $price_list_items = [];

    foreach ($entities as $entity) {
      if (empty($entity->field_price_list_item)) {
        continue;
      }
      foreach ($entity->field_price_list_item as $item) {
        $price_list_items[$item->target_id] = $item->entity;
      }

      $price_list_item_storage = \Drupal::service('entity_type.manager')
        ->getStorage('commerce_price_list_item');
      $price_list_item_storage->delete($price_list_items);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this pricelist in relation to other pricelists.'))
      ->setDefaultValue(0);
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Price list is published.'))
      ->setDefaultValue(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Price list entity.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Price list entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 2,
      ])
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
      ->setDescription(t('The start date of the Price list entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'datetime_type' => 'datetime',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date'))
      ->setDescription(t('The end date of the Price list entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'datetime_type' => 'datetime',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 4,
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

}
