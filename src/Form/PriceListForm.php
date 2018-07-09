<?php

namespace Drupal\commerce_pricelist\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Price list edit forms.
 *
 * @ingroup commerce_pricelist
 */
class PriceListForm extends ContentEntityForm {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, MessengerInterface $messenger, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->messenger = $messenger;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('messenger'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce_pricelist\Entity\PriceList */
    $store_query = $this->entityManager->getStorage('commerce_store')->getQuery();
    if ($store_query->count()->execute() == 0) {
      $link = Link::createFromRoute('Add a new store.', 'entity.commerce_store.add_page');
      $form['warning'] = [
        '#markup' => t("Products can't be created until a store has been added. @link", ['@link' => $link->toString()]),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_pricelist\Entity\PriceList $price_list */
    $price_list = $this->entity;
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;
    $form['#theme'] = ['commerce_product_form'];
    $form['#attached']['library'][] = 'commerce_product/form';

    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $price_list->getChangedTime(),
    ];

    $form['footer'] = [
      '#type' => 'container',
      '#weight' => 99,
      '#attributes' => [
        'class' => ['product-form-footer'],
      ],
    ];

    $form['status']['#group'] = 'footer';

    $last_saved = t('Not saved yet');
    if (!$price_list->isNew()) {
      $last_saved = $this->dateFormatter->format($price_list->getChangedTime(), 'short');
    }

    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
      'published' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $price_list->isPublished() ? $this->t('Activated') : $this->t('Deactivated'),
        '#access' => !$price_list->isNew(),
        '#attributes' => [
          'class' => ['entity-meta__title'],
        ],
      ],
      'changed' => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['entity-meta__last-saved', 'container-inline'],
        ],
        '#markup' => '<h4 class="label inline">' . $this->t('Last saved') . '</h4> ' . $last_saved,
      ],
      'author' => [
        '#type' => 'item',
        '#wrapper_attributes' => [
          'class' => ['author', 'container-inline'],
        ],
        '#markup' => '<h4 class="label inline">' . $this->t('Author') . '</h4> ' . $price_list->getOwner()->getDisplayName(),
      ],
    ];

    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form['author'] = [
      '#type' => 'details',
      '#title' => t('Owner information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['product-form-author'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];
    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }
    $form['price_list_store'] = [
      '#type' => 'details',
      '#title' => t('Price List Store'),
      '#open' => TRUE,
      '#group' => 'advanced',
      '#access' => !empty($form['stores']['#access']),
      '#attributes' => [
        'class' => ['product-visibility-settings'],
      ],
      '#weight' => 30,
    ];

    if (isset($form['stores'])) {
      $form['stores']['#group'] = 'price_list_store';
      $form['#after_build'][] = [get_class($this), 'hideEmptyVisibilitySettings'];
    }

    return $form;
  }

  /**
   * Hides the visibility settings if the stores widget is a hidden element.
   *
   * @param array $form
   *   The form.
   *
   * @return array
   *   The modified visibility_settings element.
   */
  public static function hideEmptyVisibilitySettings(array $form) {
    if (isset($form['stores']['widget']['target_id'])) {
      $stores_element = $form['stores']['widget']['target_id'];
      if (!Element::getVisibleChildren($stores_element)) {
        $form['price_list_store']['#printed'] = TRUE;
        // Move the stores widget out of the visibility_settings group to
        // ensure that its hidden element is still present in the HTML.
        unset($form['stores']['#group']);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    foreach ($entity->items as $item) {
      $itemEntity = $item->get('entity')->getTarget()->getValue();
      $itemEntity->setWeight($item->getValue()['weight']);
      $itemEntity->save();
    }

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Price list.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Price list.', [
          '%label' => $entity->label(),
        ]));
    }

    $form_state->setRedirect('entity.commerce_price_list.collection');
  }

}
