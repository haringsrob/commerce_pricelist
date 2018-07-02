<?php

namespace Drupal\commerce_pricelist;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class PriceListItemRepository implements PriceListItemRepositoryInterface {

  protected $priceListItemStorage;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new PriceListItemResolver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    $this->priceListItemStorage = $entity_type_manager->getStorage('commerce_price_list_item');
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function loadItems(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $today = gmdate('Y-m-d', $this->time->getRequestTime());

    $query = $this->priceListItemStorage->getQuery();
    $query->exists('price_list_id');
    $query->condition('type', $entity->getEntityTypeId());
    $query->condition('quantity', $quantity, '<=');
    $query->condition('purchased_entity', $entity->id());
    $query->condition('price_list_id.entity.store_id', $context->getStore()->id());
    $query->condition('price_list_id.entity.start_date', $today, '<=');
    $query->condition($query->orConditionGroup()
      ->condition('price_list_id.entity.end_date', $today, '>=')
      ->notExists('price_list_id.entity.end_date')
    );
    $query->condition($query->orConditionGroup()
      ->condition('price_list_id.entity.target_uid', $context->getCustomer()->id())
      ->notExists('price_list_id.entity.target_uid')
    );
    $query->condition($query->orConditionGroup()
      ->condition('price_list_id.entity.target_role', $context->getCustomer()->getRoles(), 'IN')
      ->notExists('price_list_id.entity.target_role')
    );
    $query->sort('weight');
    $results = $query->execute();
    $price_list_items = $this->priceListItemStorage->loadMultiple($results);
    // @todo fire an event here, like FilterPriceListItems
    return $price_list_items;
  }

}
