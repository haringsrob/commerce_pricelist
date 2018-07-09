<?php

namespace Drupal\commerce_pricelist\Entity;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for defining price list entities.
 *
 * @ingroup commerce_pricelist
 */
interface PriceListInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets the price list name.
   *
   * @return string
   *   Name of the price list.
   */
  public function getName();

  /**
   * Sets the price list name.
   *
   * @param string $name
   *   The price list name.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListInterface
   *   The called price list entity.
   */
  public function setName($name);

  /**
   * Gets the price list creation timestamp.
   *
   * @return int
   *   Creation timestamp of the price list.
   */
  public function getCreatedTime();

  /**
   * Sets the price list creation timestamp.
   *
   * @param int $timestamp
   *   The price list creation timestamp.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListInterface
   *   The called price list entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the price list's item IDs.
   *
   * @return int[]
   *   The price list item IDs.
   */
  public function getItemsIds();

  /**
   * Gets the price list's item list.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListItemInterface[]
   *   The price list items.
   */
  public function getItems();

  /**
   * Gets the price list start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The price list start date.
   */
  public function getStartDate();

  /**
   * Sets the price list start date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The price list start date.
   *
   * @return $this
   */
  public function setStartDate(DrupalDateTime $start_date);

  /**
   * Gets the price list end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The price list end date, or NULL
   */
  public function getEndDate();

  /**
   * Gets the store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The store entity.
   */
  public function getStore();

  /**
   * Sets the store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store entity.
   *
   * @return $this
   */
  public function setStore(StoreInterface $store);

  /**
   * Gets the store ID.
   *
   * @return int
   *   The store ID.
   */
  public function getStoreId();

  /**
   * Sets the store ID.
   *
   * @param int $store_id
   *   The store ID.
   *
   * @return $this
   */
  public function setStoreId($store_id);

  /**
   * Gets the target user ID this price list is for.
   *
   * @return int
   *   The target user ID.
   */
  public function getTargetUserId();

  /**
   * Gets the target user this price list is for.
   *
   * @return \Drupal\user\UserInterface
   *   The target user.
   */
  public function getTargetUser();

  /**
   * Sets the target user ID this price list is for.
   *
   * @param int $uid
   *   The target user ID.
   *
   * @return $this
   */
  public function setTargetUserId($uid);

  /**
   * Sets the target user this price list is for.
   *
   * @param \Drupal\user\UserInterface $user
   *   The target user.
   *
   * @return $this
   */
  public function setTargetUser(UserInterface $user);

  /**
   * Gets the target role this price list is for.
   *
   * @return string
   *   The target role ID.
   */
  public function getTargetRoleId();

  /**
   * Gets the target role this price list is for.
   *
   * @return \Drupal\user\RoleInterface
   *   The target role.
   */
  public function getTargetRole();

  /**
   * Sets the target role ID.
   *
   * @param string $rid
   *   The role ID.
   *
   * @return $this
   */
  public function setTargetRoleId($rid);

  /**
   * Sets the target role.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The target role.
   *
   * @return $this
   */
  public function setTargetRole(RoleInterface $role);

  /**
   * Gets the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight();

  /**
   * Sets the weight.
   *
   * @param int $weight
   *   The weight.
   *
   * @return $this
   */
  public function setWeight($weight);

}
