<?php

namespace Drupal\commerce_pricelist;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Price list entities.
 *
 * @ingroup commerce_pricelist
 */
class PriceListListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['store'] = $this->t('Store');
    $header['start_date'] = $this->t('Start date');
    $header['end_date'] = $this->t('End date');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_pricelist\Entity\PriceList */
    $name_url = Url::fromRoute('entity.commerce_price_list.edit_form', ['commerce_price_list' => $entity->id()]);
    $row['id'] = $entity->id();
    $row['name'] = Link::fromTextAndUrl($entity->label(), $name_url);
    $row['store'] = $entity->getStore()->label();
    $row['start_date'] = $entity->getStartDate()->format('M jS Y');
    $row['end_date'] = $entity->getEndDate() ? $entity->getEndDate()->format('M jS Y') : '—';
    $row['status'] = $entity->isPublished() ? $this->t('Activated') : $this->t('Deactivated');
    return $row + parent::buildRow($entity);
  }

}
