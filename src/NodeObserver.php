<?php

namespace Drupal\change_requests;

use Drupal\changed_fields\NodeSubject;
use Drupal\changed_fields\ObserverInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\change_requests\Events\ChangeRequests;
use Drupal\change_requests\Plugin\FieldPatchPluginManager;
use Drupal\Core\Session\AccountProxy;
use SplSubject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class BasicUsageObserver.
 */
class NodeObserver implements ObserverInterface, ContainerInjectionInterface {

  /**
   * The Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager|null
   */
  private $entityTypeManager;

  /**
   * The change_requests plugin manager.
   *
   * @var \Drupal\change_requests\Plugin\FieldPatchPluginManager|null
   */
  private $pluginManager;

  /**
   * The Drupal config manager.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|null
   */
  private $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy|null
   */
  private $currentUser;

  /**
   * @var AttachService
   */
  private $attachTo;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    FieldPatchPluginManager $plugin_manager,
    ImmutableConfig $config,
    AccountProxy $currentUser,
    AttachService $attach_to
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
    $this->config = $config;
    $this->currentUser = $currentUser;
    $this->attachTo = $attach_to;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field_patch_plugin'),
      $container->get('config.manager')->getConfigFactory()->get('change_requests.config'),
      $container->get('current_user'),
      $container->get('change_requests.attach_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = [];
    foreach ($this->config->get('node_types') as $node_type) {
      $info[$node_type] = array_keys($this->pluginManager->getPatchableFields($node_type));
    }
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function update(SplSubject $nodeSubject) {
    /** @var \Drupal\changed_fields\NodeSubject $nodeSubject */
    $node = $nodeSubject->getNode();
    if ($node->isNewRevision()) {
      $diff = $this->getNodeDiff($nodeSubject);
      /** @var \Drupal\change_requests\Entity\Patch $patch */
      $patch = $this->getPatch($node->id(), $node->getEntityTypeId(), $node->bundle());

      $patch
        ->set('rvid', $node->original->getRevisionId())
        ->set('patch', $diff)
        ->set('message', $node->getRevisionLogMessage() ?: ' ')
        ->set('uid', $this->currentUser->id());
      $patch->save();

      drupal_set_message(t('Thank you. Your change request has been saved and is to be confirmed.'), 'status', TRUE);

      if ($attach_to = \Drupal::request()->get('attach_to')) {
        $this->attachTo->attachPatchTo($attach_to, $patch->id());
      }

      $response = new RedirectResponse($patch->url());
      $response->send();
      exit;
    }
  }

  /**
   * Get the revision diff value.
   *
   * @param \Drupal\changed_fields\NodeSubject $nodeSubject
   *   The changed field api output.
   *
   * @return array
   *   The result diff.
   */
  protected function getNodeDiff(NodeSubject $nodeSubject) {
    $diff = [];
    $changedFields = $nodeSubject->getChangedFields();
    $node = $nodeSubject->getNode();
    foreach ($changedFields as $name => $values) {
      $field_type = $node->getFieldDefinition($name)->getType();
      $diff[$name] = $this->pluginManager->getDiff($field_type, $values['old_value'], $values['new_value']);
    }
    return $diff;
  }

  /**
   * Returns an existing Patch instance or new created if none exists.
   *
   * @param int $nid
   *   The node ID.
   * @param string $type
   *   The node version ID.
   * @param string $bundle
   *   Bundle ID if exists.
   *
   * @return \Drupal\core\Entity\EntityInterface
   *   Patch entity prepared with node and version IDs.
   */
  protected function getPatch($nid, $type, $bundle = '') {
    $storage = $this->entityTypeManager->getStorage('patch');
    $params = [
      'status' => ChangeRequests::CR_STATUS_ACTIVE,
      'rtype' => $type,
      'rbundle' => $bundle,
      'rid' => $nid,
      'rvid' => 0,
    ];
    $patch = $storage->create($params);
    return $patch;
  }

}
