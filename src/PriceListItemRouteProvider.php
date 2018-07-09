<?php

namespace Drupal\commerce_pricelist;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for the Price list item entity.
 */
class PriceListItemRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    $route->setOption('parameters', [
      'commerce_price_list' => [
        'type' => 'entity:commerce_price_list',
      ],
    ]);
    // Price list items can be created if the price list can be updated.
    $requirements = $route->getRequirements();
    unset($requirements['_entity_create_access']);
    $requirements['_entity_access'] = 'commerce_price_list.update';
    $route->setRequirements($requirements);

    return $route;
  }

}
