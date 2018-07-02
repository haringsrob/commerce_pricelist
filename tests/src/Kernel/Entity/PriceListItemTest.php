<?php

namespace Drupal\Tests\commerce_pricelist\Kernel\Entity;

use Drupal\commerce_price\Price;
use Drupal\commerce_pricelist\Entity\PriceListItem;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce_pricelist\Kernel\PriceListKernelTestBase;

/**
 * Tests the price list item entity.
 *
 * @group commerce_pricelist
 */
class PriceListItemTest extends PriceListKernelTestBase {

  public function testPriceListItem() {
    /** @var \Drupal\commerce_pricelist\Entity\PriceListItem $price_list_item */
    $price_list_item = PriceListItem::create([
      'type' => 'commerce_product_variation',
    ]);
    $price_list_item->save();

    $this->assertTrue($price_list_item->hasField('price_list_id'));

    $price_list_target_type = $price_list_item->get('price_list_id')->getFieldDefinition()->getSetting('target_type');
    $this->assertEquals('commerce_price_list', $price_list_target_type);
    $price_list_target_type = $price_list_item->get('price_list_id')->getFieldDefinition()->getSetting('handler_settings');
    $this->assertEquals([
      'target_bundles' => [
        'commerce_product_variation' => 'commerce_product_variation',
      ],
    ], $price_list_target_type);

    $purchased_entity_target_type = $price_list_item->get('purchased_entity')->getFieldDefinition()->getSetting('target_type');
    $this->assertEquals('commerce_product_variation', $purchased_entity_target_type);

    $price_list_item->setQuantity(4);
    $this->assertEquals(4, $price_list_item->getQuantity());

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => 'default',
    ]);
    $variation->save();

    $this->assertFalse($price_list_item->hasPurchasedEntity());
    $price_list_item->setPurchasedEntityId($variation->id());
    $this->assertTrue($price_list_item->hasPurchasedEntity());
    $this->assertEquals($variation->id(), $price_list_item->getPurchasedEntityId());

    $price = new Price('5.00', 'USD');
    $price_list_item->setPrice($price);
    $this->assertEquals($price, $price_list_item->getPrice());

    $this->assertTrue($price_list_item->isPublished());
    $price_list_item->setUnpublished();
    $this->assertFalse($price_list_item->isPublished());
  }

}
