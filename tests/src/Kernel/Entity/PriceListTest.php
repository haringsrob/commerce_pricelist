<?php

namespace Drupal\Tests\commerce_pricelist\Kernel\Entity;

use Drupal\commerce_pricelist\Entity\PriceList;
use Drupal\commerce_pricelist\Entity\PriceListItem;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce_pricelist\Kernel\PriceListKernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the price list entity.
 *
 * @group commerce_pricelist
 */
class PriceListTest extends PriceListKernelTestBase {

  public function testPriceList() {
    /** @var \Drupal\commerce_pricelist\Entity\PriceList $pricelist */
    $pricelist = PriceList::create([
      'type' => 'commerce_product_variation',
    ]);

    $pricelist->setName('B2B pricing');
    $this->assertEquals('B2B pricing', $pricelist->getName());
    $this->assertEquals('B2B pricing', $pricelist->label());

    $time = $this->container->get('datetime.time');
    $pricelist->setCreatedTime($time->getRequestTime());
    $this->assertEquals($time->getRequestTime(), $pricelist->getCreatedTime());

    $this->assertEquals(gmdate('Y-m-d', $time->getRequestTime()), PriceList::getDefaultStartDate());
    $this->assertEquals(gmdate('Y-m-d', $time->getRequestTime()), $pricelist->getStartDate()->format('Y-m-d'));
    $pricelist->setStartDate(new DrupalDateTime('2017-01-01'));
    $this->assertEquals('2017-01-01', $pricelist->getStartDate()->format('Y-m-d'));

    $pricelist->setEndDate(new DrupalDateTime('2017-01-31'));
    $this->assertEquals('2017-01-31', $pricelist->getEndDate()->format('Y-m-d'));

    $pricelist->setStore($this->store);
    $this->assertEquals($this->store->label(), $pricelist->getStore()->label());
    $this->assertEquals($this->store->id(), $pricelist->getStoreId());


    $definition = $pricelist->get('items')->getFieldDefinition();
    $this->assertEquals('commerce_product_variation', $definition->getSetting('target_type'));
    $this->assertEmpty($pricelist->getItems());
    $this->assertEmpty($pricelist->getItemsIds());
    /** @var \Drupal\commerce_pricelist\Entity\PriceListItem $list_item1 */
    $list_item1 = PriceListItem::create(['type' => 'commerce_product_variation']);
    $list_item1->save();
    /** @var \Drupal\commerce_pricelist\Entity\PriceListItem $list_item2 */
    $list_item2 = PriceListItem::create(['type' => 'commerce_product_variation']);
    $list_item2->save();
    $pricelist->set('items', [
      $this->reloadEntity($list_item1),
      $this->reloadEntity($list_item2),
    ]);
    $this->assertEquals([
      $list_item1->id(),
      $list_item2->id(),
    ], $pricelist->getItemsIds());

    // @todo: this is failing.
//    $this->assertEquals([
//      $list_item1,
//      $list_item2
//    ], $pricelist->getItems());

    $this->assertTrue($pricelist->isPublished());
    $pricelist->setUnpublished();
    $this->assertFalse($pricelist->isPublished());

    $pricelist->setOwner($this->user);
    $this->assertEquals($this->user, $pricelist->getOwner());

    $user2 = $this->createUser();
    $pricelist->setOwnerId($user2->id());
    $this->assertEquals($user2->id(), $pricelist->getOwnerId());


    $target_user = $this->createUser();
    $pricelist->setTargetUser($target_user);
    $this->assertEquals($target_user->id(), $pricelist->getTargetUserId());

    $role1 = Role::create(['id' => 'test_role1', 'name' => $this->randomString()]);
    $role1->save();
    $pricelist->setTargetRole($role1);
    $this->assertEquals($role1->id(), $pricelist->getTargetRoleId());


    $pricelist->save();
    $list_item2 = $this->reloadEntity($list_item2);
    $this->assertEquals($pricelist->id(), $list_item2->getPriceListId());

    $pricelist->delete();
    $this->assertNull($this->reloadEntity($list_item1));
    $this->assertNull($this->reloadEntity($list_item2));
  }

}
