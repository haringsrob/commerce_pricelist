<?php

namespace Drupal\commerce_pricelist;

use Drupal\Core\Link;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Price list entities.
 *
 * @ingroup commerce_pricelist
 */
class PriceListItemListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Product ID');
    $header['quantity'] = $this->t('Quantity');
    $header['price'] = $this->t('Price');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_pricelist\Entity\PriceListItem */

    if ($entity->bundle() == 'commerce_product_variation') {
      $row['id'] = $entity->getPurchasedEntity()->getSku();
    }
    else {
      $row['id'] = $entity->getPurchasedEntityId();
    }
    $row['quantity'] = $entity->getQuantity();
    $row['price'] = [
      'data' => [
        '#type' => 'inline_template',
        '#template' => '{{price|commerce_price_format}}',
        '#context' => [
          'price' => $entity->getPrice(),
        ],
      ]
    ];
    $row['status'] = $entity->isPublished() ? $this->t('Activated') : $this->t('Deactivated');
    return $row + parent::buildRow($entity);
  }

}
