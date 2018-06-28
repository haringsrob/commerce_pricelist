<?php

namespace Drupal\Tests\commerce_pricelist\Kernel;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Tests the the action of saving purchased entity.
 *
 * @group commerce_pricelist
 */
class PriceListBundlesTest extends PriceListKernelTestBase {

  /**
   * There should be price list bundles based on available purchasable entities.
   */
  public function testAvailableBundles() {
    $bundle_info = $this->container->get('entity_type.bundle.info')->getAllBundleInfo();
    $price_list_bundles = $bundle_info['commerce_price_list'];
    $price_list_item_bundles = $bundle_info['commerce_price_list_item'];

    $this->assertCount(1, $price_list_bundles);
    $this->assertTrue(isset($price_list_bundles['commerce_product_variation']));
    $this->assertCount(1, $price_list_item_bundles);
    $this->assertTrue(isset($price_list_item_bundles['commerce_product_variation']));

    // Install our test module and verify we have new bundles.
    $this->installModule('commerce_pricelist_bundle_test');
    $this->container->get('entity_type.manager')->clearCachedDefinitions();
    $this->container->get('entity_type.bundle.info')->clearCachedBundles();


    $bundle_info = $this->container->get('entity_type.bundle.info')->getAllBundleInfo();
    $price_list_bundles = $bundle_info['commerce_price_list'];
    $price_list_item_bundles = $bundle_info['commerce_price_list_item'];

    $this->assertCount(2, $price_list_bundles);
    $this->assertTrue(isset($price_list_bundles['commerce_pricelist_widget']));
    $this->assertCount(2, $price_list_item_bundles);
    $this->assertTrue(isset($price_list_item_bundles['commerce_pricelist_widget']));
  }

}
