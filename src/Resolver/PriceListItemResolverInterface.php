<?php

namespace Drupal\commerce_pricelist\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

interface PriceListItemResolverInterface {

  /**
   * Resolves available price lists of a given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListItemInterface[]
   *   An array of price list items.
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context);

}
