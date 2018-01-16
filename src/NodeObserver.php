<?php
/**
 * @file
 * Contains BasicUsageObserver.php.
 */

namespace Drupal\patch_revision;

use Drupal\changed_fields\NodeSubject;
use Drupal\changed_fields\ObserverInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\patch_revision\Entity\Patch;
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


  function __construct() {
    $container = \Drupal::getContainer();
    $this->entity_type_manager = $container->get('entity_type.manager');
    $this->diffService = $container->get('patch_revision.diff');
    $this->plugin_manager = $container->get('plugin.manager.field_patch_plugin');
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
    // ToDo check nodes, what bundles exist, what fields are included, and if plugins(todo) exists.
    return [
      'rule' => [
        'title',
        'body',
      ],
      'problem' => [
        'title',
        'body',
      ],
    ];
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
      $patch = $this->getPatch($node->id());
      $patch->set('patch', $diff);
      $patch->save();

      drupal_set_message(t('Improvement has been saved and is to be confirmed.'), 'status', TRUE);


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
   * @param $nid int
   *   The node ID.
   * @param $vid int
   *   The node version ID.
   *
   * @return \Drupal\patch_revision\Entity\Patch
   *   Patch entity prepared with node and version IDs.
   */
  protected function getPatch($nid) {
    $storage = $this->entity_type_manager->getStorage('patch');
    $params = ['rid' => $nid, 'rvid' => 666];
    $patch = $storage->create($params);
    return $patch;
  }

}