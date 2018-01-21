<?php
/**
 * @file
 * Contains BasicUsageObserver.php.
 */

namespace Drupal\patch_revision;

use Drupal\changed_fields\NodeSubject;
use Drupal\changed_fields\ObserverInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\patch_revision\Entity\Patch;
use Drupal\patch_revision\Events\PatchRevision;
use Drupal\patch_revision\Plugin\FieldPatchPluginManager;
use SplSubject;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class BasicUsageObserver.
 */
class NodeObserver implements ObserverInterface {

  /**
   * @var DiffService|NULL
   */
  private $diffService;

  /**
   * @var EntityTypeManager|NULL
   */
  private $entity_type_manager;

  /**
   * @var FieldPatchPluginManager|NULL
   */
  private $plugin_manager;

  /**
   * @var ConfigManagerInterface|NULL
   */
  private $config;


  function __construct() {
    $container = \Drupal::getContainer();
    $this->entity_type_manager = $container->get('entity_type.manager');
    $this->diffService = $container->get('patch_revision.diff');
    $this->plugin_manager = $container->get('plugin.manager.field_patch_plugin');
    $this->config = $container->get('config.manager')->getConfigFactory()->get('patch_revision.config');
  }

  /**
   * @return DiffService|mixed|NULL
   */
  protected function getDiffService() {
    return $this->diffService;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = [];
    foreach ($this->config->get('node_types') as $node_type) {
      $info[$node_type] = array_keys($this->plugin_manager->getPatchableFields($node_type));
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
      /** @var Patch $patch */
      $patch = $this->getPatch($node->id(), $node->getEntityTypeId(), $node->bundle());

      $patch
        ->set('patch', $diff)
        ->set('message', $node->getRevisionLogMessage() ?: ' ')
        ->set('uid', \Drupal::currentUser()->id());
      $patch->save();

      drupal_set_message(t('Thanks. Your improvement has been saved and is to be confirmed.'), 'status', TRUE);


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
  protected function getNodeDiff($nodeSubject) {
    $diff = [];
    $changedFields = $nodeSubject->getChangedFields();
    $node = $nodeSubject->getNode();
    foreach ($changedFields as $name => $values) {
      $field_type = $node->getFieldDefinition($name);
      $diff[$name] = $this->getDiffService()->getDiff($field_type, $values['old_value'], $values['new_value']);
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
   * @return \Drupal\patch_revision\Entity\Patch
   *   Patch entity prepared with node and version IDs.
   */
  protected function getPatch($nid, $type, $bundle = '') {
    $storage = $this->entity_type_manager->getStorage('patch');
    $params = [
      'status' => PatchRevision::PR_STATUS_ACTIVE,
      'rtype' => $type,
      'rbundle' => $bundle,
      'rid' => $nid,
      'rvid' => 0
    ];
    $patch = $storage->create($params);
    return $patch;
  }

}