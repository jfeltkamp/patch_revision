<?php

namespace Drupal\change_requests\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Link;
use Drupal\node\NodeInterface;
use Drupal\change_requests\Entity\Patch;
use Drupal\change_requests\Events\ChangeRequests;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Rate routes.
 */
class PatchesOverview extends ControllerBase {

  /**
   * The original node id.
   *
   * @var int
   */
  private $nid;

  /**
   * The original node itself.
   *
   * @var \Drupal\node\NodeInterface
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
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * DateFormatterInterface definition.
   *
   * @var \Drupal\change_requests\Entity\ChangeRequests
   */
  protected $constants;

  /**
   * DateFormatterInterface definition.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    DateFormatterInterface $date_formatter,
    TimeInterface $time
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityType = $this->entityTypeManager->getDefinition('patch');
    $this->entityStorage = $this->entityTypeManager->getStorage('patch');
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
    $this->constants = new ChangeRequests();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
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
   * Load the original entity.
   *
   * @return \Drupal\node\NodeInterface
   *   The original node, the patch is created from.
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
    $header['status'] = $this->t('Status');
    $header['operations'] = $this->t('Operations');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(Patch $entity) {
    /* @var $entity \Drupal\change_requests\Entity\Patch */
    $row['created']['data'] = $this->getDate($entity);
    /** @var \Drupal\user\UserInterface $user */
    $user = $entity->get('uid')->entity;
    $row['user'] = Link::createFromRoute(
      $user->label(),
      'entity.user.canonical',
      ['user' => $user->id()]
    );

    $row['message']['data'] = $entity->get('message')->getString();
    $row['status']['data'] = $this->getStatus($entity->get('status')->getString());
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
      '#title' => $this->t('Change requests for "@title"', ['@title' => $this->node->label()]),
      '#rows' => [],
      '#empty' => $this->t('There is no @label yet.', ['@label' => strtolower($this->entityType->getLabel())]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => ['patch_list:node:' . $this->nid],
        'max-age' => Cache::PERMANENT,
      ],
      '#attached' => ['library' => ['change_requests/cr_status']],
    ];
    foreach ($this->load() as $entity) {
      /** @var \Drupal\change_requests\Entity\Patch $entity */
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
   * @param \Drupal\change_requests\Entity\Patch $entity
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
        'title' => $this->t('Apply change request'),
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
   * @param \Drupal\change_requests\Entity\Patch $entity
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
   * Get the creation or recent change date.
   *
   * @param \Drupal\change_requests\Entity\Patch $entity
   *   The patch entity.
   * @param string $mode
   *   Created or changed time.
   *
   * @return string
   *   A date formatted date string.
   */
  protected function getDate(Patch $entity, $mode = 'created') {
    $timestamp = (int) $entity->get($mode)->getString();
    $interval = $this->time->getRequestTime() - $timestamp;

    $date = $interval < (60 * 60 * 12)
      ? $this->t('@time ago', ['@time' => $this->dateFormatter->formatInterval($interval, 2)])
      : $this->dateFormatter->format($timestamp, 'short');
    return $date;
  }

  /**
   * Returns formatted.
   *
   * @param int|string $value
   *   The integer status ID.
   *
   * @return array
   *   The formatted string for List overview.
   */
  protected function getStatus($value) {
    $value = (int) $value;
    $literal = $this->constants->getStatusLiteral($value);
    $class = $this->constants->getStatus($value);
    return [
      '#markup' => $literal,
      '#prefix' => "<span class=\"cr-status {$class}\">",
      '#suffix' => '</span>',
    ];
  }

}
