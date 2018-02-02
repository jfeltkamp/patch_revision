<?php

namespace Drupal\patch_revision\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Link;
use Drupal\node\NodeInterface;
use Drupal\patch_revision\Entity\Patch;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Rate routes.
 */
class PatchesOverview extends ControllerBase {

  /**
   * @var integer
   */
  private $nid;

  /**
   * @var NodeInterface
   */
  private $node;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityStorage;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * DateFormatterInterface definition.
   * @var \Drupal\Core\Datetime\DateFormatterInterface;
   */
  protected $dateFormatter;

  /**
   * Constructs a new DefaultController object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, DateFormatterInterface $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityType = $this->entityTypeManager->getDefinition('patch');
    $this->entityStorage = $this->entityTypeManager->getStorage('patch');
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }


  /**
   * Display list of existing patches.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node for which to display patches.
   *
   * @return array
   *   The render array.
   */
  public function overview(NodeInterface $node) {
    $this->nid = $node->id();
    $this->node = $node;

    return $this->render();
  }

  /**
   *
   */
  protected function load() {
    $result = $this->entityStorage->loadByProperties([
      'rid' => $this->nid,
    ]);
    return array_reverse($result);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['created'] = $this->t('Time created');
    $header['user'] = $this->t('By user');
    $header['message'] = $this->t('Log message');
    $header['operations'] = $this->t('Operations');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(Patch $entity) {
    /* @var $entity \Drupal\patch_revision\Entity\Patch */
    $row['created']['data'] = $this->getDate($entity);
    /** @var UserInterface $user */
    $user = $entity->get('uid')->entity;
    $row['user'] = Link::createFromRoute(
      $user->label(),
      'entity.user.canonical',
      ['user' => $user->id()]
    );

    $row['message']['data'] = $entity->get('message')->getString();
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as renderable array for table.html.twig.
   *
   * @todo Add a link to add a new item to the #empty text.
   */
  protected function render() {

    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->t('Patches for "@title"', ['@title' => $this->node->label()]),
      '#rows' => [],
      '#empty' => $this->t('There is no @label yet.', ['@label' => $this->entityType->getLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => ['patch_list:node:'.$this->nid],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    foreach ($this->load() as $entity) {
      /** @var Patch $entity */
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(Patch $entity) {
    $operations = $this->getDefaultOperations($entity);
    $operations += $this->moduleHandler()->invokeAll('entity_operation', [$entity]);
    $this->moduleHandler->alter('entity_operation', $operations, $entity);
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $operations;
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations(Patch $entity) {
    $operations = [];
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['canonical'] = [
        'title' => $this->t('View'),
        'weight' => 10,
        'url' => $entity->urlInfo('canonical'),
      ];
    }
    if ($entity->access('apply') && $entity->hasLinkTemplate('apply-form')) {
      $operations['apply'] = [
        'title' => $this->t('Apply improvement'),
        'weight' => 30,
        'url' => $entity->urlInfo('apply-form'),
      ];
    }
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 50,
        'url' => $entity->urlInfo('edit-form'),
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->urlInfo('delete-form'),
      ];
    }

    return $operations;
  }

  /**
   * Builds a renderable list of operation links for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which the linked operations will be performed.
   *
   * @return array
   *   A renderable array of operation links.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::buildRow()
   */
  public function buildOperations(Patch $entity) {
    $build = [
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
    ];

    return $build;
  }

  /**
   * @param Patch $entity
   *   The patch entity.
   * @param string $mode
   *   Created or changed time.
   * @return string
   */
  protected function getDate(Patch $entity, $mode = 'created') {
    $timestamp = (int)$entity->get($mode)->getString();
    $interval = \Drupal::time()->getRequestTime() - $timestamp;

    $date = $interval < (60*60*12)
      ? $this->t('@time ago', ['@time' => $this->dateFormatter->formatInterval($interval, 2)])
      : $this->dateFormatter->format($timestamp, 'short');
    return $date;
  }

}
