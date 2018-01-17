<?php

namespace Drupal\patch_revision;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\node\NodeInterface;
use Drupal\patch_revision\Entity\Patch;

/**
 * View builder handler for nodes.
 */
class PatchViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode);

    return $defaults;
  }


  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    parent::alterBuild($build, $entity, $display, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\patch_revision\Entity\Patch $entity */
    $view = parent::view($entity, $view_mode, $langcode);

    /** @var NodeInterface[] $patches */
    $patches = $entity->get('patch')->getValue();
    $patch = count($patches) ? $patches[0] : [];
    foreach ($patch as $field_name => $value) {
      $field_patch_plugin = $entity->getPatchPluginFromOrigFieldName($field_name);
      $field_label = $entity->getOrigFieldLabel($field_name);
      $view[$field_name] = $field_patch_plugin->getFieldPatchView($field_label, $value);
    }
    return $view;
  }

}
