<?php

namespace Drupal\commerce_pricelist\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\commerce_pricelist\PriceListItemRepositoryInterface;

/**
 * Class PriceListDefaultBasePriceResolver.
 */
class PriceListItemPriceResolver implements PriceResolverInterface {

  protected $priceListItemRepository;

  public function __construct(PriceListItemRepositoryInterface $price_list_item_repository) {
    $this->priceListItemRepository = $price_list_item_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $price_list_items = $this->priceListItemRepository->loadItems($entity, $quantity, $context);
    if (!empty($price_list_items)) {
      // If multiple were returned, trust that the first one is the expected
      // result due to sorting and weights.
      $price_list_item = reset($price_list_items);
      return $price_list_item->getPrice();
    }
  }

}
