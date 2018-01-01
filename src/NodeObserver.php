<?php
/**
 * @file
 * Contains BasicUsageObserver.php.
 */

namespace Drupal\patch_revision;

use Drupal\changed_fields\ObserverInterface;
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
   * @return DiffService|mixed|NULL
   */
  protected function getDiffService() {
    if(!$this->diffService) {
      $this->diffService = \Drupal::service('patch_revision.diff');
    }
    return $this->diffService;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      'rule' => [
        'body',
      ],
      'problem' => [
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
    $diff = $this->getNodeDiff($nodeSubject->getChangedFields());
    if ($node->hasField('revision_diff')) {
      $node->set('revision_diff', [$diff]);
      drupal_set_message('Diff has been added.');
    }
  }

  /**
   * Get the revision diff value.
   *
   * @param array $changedFields
   *   The changed field api output.
   *
   * @return array
   *   The result diff.
   */
  protected function getNodeDiff(array $changedFields) {

    $diff = [];
    foreach ($changedFields as $name => $values) {
      /* @ToDo find a clean way to exactly diff multiple values. */
      $str_src    = $values['old_value']['0']['value'];
      $str_target = $values['new_value']['0']['value'];
      $diff[$name] = $this->getDiffService()->getDiff($str_src, $str_target);
    }
    return $diff;
  }

}