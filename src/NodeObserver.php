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
      $patch = $this->getPatch($node->id(), $node->getRevisionId());
      $patch->set('patch', $diff);
      $patch->save();
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
      $field_type = $node->getFieldDefinition($name)->getType();


      // ToDo XXX create Plugin for different field types

      $counts = max([count($values['old_value']), count($values['new_value'])]);
      for ($i = 0; $i <= $counts; $i++) {
        $str_src    = isset($values['old_value'][$i]) ? $values['old_value'][$i]['value'] : '';
        $str_target = isset($values['new_value'][$i]) ? $values['old_value'][$i]['value'] : '';

        $diff[$name][$i] = $this->getDiffService()->getDiff($str_src, $str_target);
      }
    }
    return $diff;
  }

  protected function getPatch($nid, $vid) {
    $storage = $this->entity_type_manager->getStorage('patch');
    $params = ['rid' => $nid, 'rvid' => $vid];
    $patches = $storage->loadByProperties($params);
    if (!$patches) {
      $patch = $storage->create($params);
    } else {
      $patch = $patches[0];
    }
    return $patch;
  }

}