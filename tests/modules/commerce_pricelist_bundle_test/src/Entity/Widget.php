<?php

namespace Drupal\commerce_pricelist_bundle_test\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_pricelist_widget",
 *   label = @Translation("Test entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\entity_test\EntityTestListBuilder",
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\entity_test\EntityTestViewsData"
 *   },
 *   base_table = "entity_test",
 *   admin_permission = "administer entity_test content",
 *   persistent_cache = FALSE,
 *   list_cache_contexts = { "entity_test_view_grants" },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/entity_test/{entity_test}",
 *     "add-form" = "/entity_test/add",
 *     "edit-form" = "/entity_test/manage/{entity_test}/edit",
 *     "delete-form" = "/entity_test/delete/entity_test/{entity_test}",
 *   },
 *   field_ui_base_route = "entity.entity_test.admin_form",
 * )
 *
 * Note that this entity type annotation intentionally omits the "create" link
 * template. See https://www.drupal.org/node/2293697.
 */
class Widget extends EntityTest implements PurchasableEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTitle() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    return NULL;
  }

}
