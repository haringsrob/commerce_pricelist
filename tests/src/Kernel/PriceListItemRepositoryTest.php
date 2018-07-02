<?php

namespace Drupal\Tests\commerce_pricelist\Kernel;

use Drupal\commerce\Context;
use Drupal\commerce_price\Price;
use Drupal\commerce_pricelist\Entity\PriceList;
use Drupal\commerce_pricelist\Entity\PriceListItem;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\user\Entity\Role;

class PriceListItemRepositoryTest extends PriceListKernelTestBase {

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

  /**
   * Tests resolving a price list item with no price list conditons.
   */
  public function testSimplePriceList() {
    $context = new Context($this->user, $this->store);
    $resolver = $this->container->get('commerce_pricselist.price_list_item_repository');

    $resolved_price_list_items = $resolver->loadItems($this->variation, 1, $context);
    $this->assertNotEmpty($resolved_price_list_items);

    /** @var \Drupal\commerce_pricelist\Entity\PriceListItem $resolved_price_list_item */
    $resolved_price_list_item = reset($resolved_price_list_items);
    $this->assertEquals(new Price('5.00', 'USD'), $resolved_price_list_item->getPrice());
    $this->assertEquals($this->priceList->id(), $resolved_price_list_item->getPriceListId());

    $other_variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('8.00', 'USD'),
    ]);
    $other_variation->save();

    $resolved_price_list_items = $resolver->loadItems($other_variation, 1, $context);
    $this->assertEmpty($resolved_price_list_items);
  }

  /**
   * Tests resolving a price list item where the price list has start/end dates.
   */
  public function testPriceListWithDates() {
    $context = new Context($this->user, $this->store);
    $resolver = $this->container->get('commerce_pricselist.price_list_item_repository');

    $this->priceList->setStartDate(new DrupalDateTime('-3 months'));
    $this->priceList->setEndDate(new DrupalDateTime('+1 year'));
    $this->priceList->save();

    $resolved_price_list_items = $resolver->loadItems($this->variation, 1, $context);
    $this->assertNotEmpty($resolved_price_list_items);

    // Set the price list to start in the future.
    $this->priceList->setStartDate(new DrupalDateTime('+1 month'));
    $this->priceList->save();

    $resolved_price_list_items = $resolver->loadItems($this->variation, 1, $context);
    $this->assertEmpty($resolved_price_list_items);

    // Expired.
    $this->priceList->setStartDate(new DrupalDateTime('-3 months'));
    $this->priceList->setEndDate(new DrupalDateTime('-1 month'));
    $this->priceList->save();

    $resolved_price_list_items = $resolver->loadItems($this->variation, 1, $context);
    $this->assertEmpty($resolved_price_list_items);
  }

  public function testWithStore() {
    $context = new Context($this->user, $this->store);
    $resolver = $this->container->get('commerce_pricselist.price_list_item_repository');

    $new_store = $this->createStore();
    $this->priceList->setStore($new_store);
    $this->priceList->save();

    $resolved_price_list_items = $resolver->loadItems($this->variation, 1, $context);
    $this->assertEmpty($resolved_price_list_items);

    $context = new Context($this->user, $new_store);
    $resolved_price_list_items = $resolver->loadItems($this->variation, 1, $context);
    $this->assertNotEmpty($resolved_price_list_items);
  }

  public function testByTargetUser() {
    $context = new Context($this->user, $this->store);
    $resolver = $this->container->get('commerce_pricselist.price_list_item_repository');

    $target_user = $this->createUser();
    $this->priceList->setTargetUser($target_user);
    $this->priceList->save();

    $resolved_price_list_items = $resolver->loadItems($this->variation, 1, $context);
    $this->assertEmpty($resolved_price_list_items);

    $context = new Context($target_user, $this->store);
    $resolved_price_list_items = $resolver->loadItems($this->variation, 1, $context);
    $this->assertNotEmpty($resolved_price_list_items);
  }

  public function testByTargetRole() {
    $context = new Context($this->user, $this->store);
    $resolver = $this->container->get('commerce_pricselist.price_list_item_repository');

    $target_role = Role::create([
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ]);
    $this->priceList->setTargetRole($target_role);
    $this->priceList->save();

    $resolved_price_list_items = $resolver->loadItems($this->variation, 1, $context);
    $this->assertEmpty($resolved_price_list_items);

    $this->user->addRole($target_role->id());
    $this->user->save();

    $resolved_price_list_items = $resolver->loadItems($this->variation, 1, $context);
    $this->assertNotEmpty($resolved_price_list_items);
  }

}
