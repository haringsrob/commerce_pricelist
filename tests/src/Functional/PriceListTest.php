<?php

namespace Drupal\Tests\commerce_pricelist\Functional;

use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * @group commerce_pricelist
 */
class PriceListTest extends CommerceBrowserTestBase {

  public static $modules = [
    'commerce_product',
    'commerce_pricelist',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_price_list',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creation of a price list.
   */
  public function testCreatePriceList() {
    $this->drupalGet(Url::fromRoute('entity.commerce_price_list.collection')->toString());
    $this->clickLink('Add Price list');

    $page = $this->getSession()->getPage();
    $page->fillField('Name', 'Black Friday 2018');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('Created the Black Friday 2018 Price list.');

    // Edit the price list.
    $this->getSession()->getPage()->clickLink('Edit');
    $this->assertSession()->pageTextContains('Edit Black Friday 2018');
    $tabs = $page->find('xpath', '//nav');
    $this->assertNotEmpty($tabs->findLink('Edit'));
    $this->assertNotEmpty($tabs->findLink('Import'));
    $this->assertNotEmpty($tabs->findLink('Prices'));
    $this->assertNotEmpty($tabs->findLink('Delete'));
  }

}
