<?php

namespace Drupal\Tests\commerce_pricelist\Kernel;


use Drupal\commerce\Context;
use Drupal\commerce_price\Price;
use Drupal\commerce_pricelist\Entity\PriceList;
use Drupal\commerce_pricelist\Entity\PriceListItem;
use Drupal\commerce_product\Entity\ProductVariation;

class PriceListItemPriceResolverTest extends PriceListKernelTestBase {

  /**
   * The test price list.
   *
   * @var \Drupal\commerce_pricelist\Entity\PriceList
   */
  protected $priceList;

  /**
   * The test price list item.
   * @var \Drupal\commerce_pricelist\Entity\PriceListItem
   */
  protected $priceListItem;

  /**
   * The test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  protected function setUp() {
    parent::setUp();

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('8.00', 'USD'),
    ]);
    $variation->save();
    $this->variation = $this->reloadEntity($variation);

    $price_list_item = PriceListItem::create([
      'type' => 'commerce_product_variation',
      'price' => new Price('5.00', 'USD'),
      'quantity' => 1,
      'purchased_entity' => $variation,
    ]);
    $price_list_item->save();
    $price_list = PriceList::create([
      'type' => 'commerce_product_variation',
      'items' => [$price_list_item],
      'store_id' => $this->store->id(),
    ]);
    $price_list->save();
    $this->priceList = $this->reloadEntity($price_list);
    $this->priceListItem = $this->reloadEntity($price_list_item);
  }

  public function testPriceResolving() {
    $context = new Context($this->user, $this->store);

    $other_variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('18.00', 'USD'),
    ]);
    $other_variation->save();

    $chain_price_resolver = $this->container->get('commerce_price.chain_price_resolver');

    $resolved_price = $chain_price_resolver->resolve($this->variation, 1, $context);
    $this->assertNotEquals($this->variation->getPrice(), $resolved_price);

    $this->priceListItem->setQuantity(10);
    $this->priceListItem->save();

    $resolved_price = $chain_price_resolver->resolve($this->variation, 1, $context);
    $this->assertEquals($this->variation->getPrice(), $resolved_price);
  }

}
