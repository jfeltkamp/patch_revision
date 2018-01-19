<?php

namespace Drupal\patch_revision;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
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

    if (FALSE && $entity->originalEntity()) {
      $nid = $entity->originalEntity()->id();
      // Set page title.
      $url = Url::fromRoute('entity.node.canonical', ['node' => $nid]);
      $project_link = Link::fromTextAndUrl($entity->originalEntity()->label(), $url);
      $view['#title'] = $this->t('Improvement for <em>@type: @title</em>', [
        '@type' => $entity->originalEntity()->type->entity->label(),
        '@title' => $project_link->toString()
      ]);
    } else {
      $view['#title'] = $this->t('Display patch in a view.');
    }

    // Set Creator view.
    $creator = $entity->getCreator();
    $view['creator'] =  $creator
      ? user_view($creator, 'compact')
      : ['#markup' => ''];

    // Build field patches views.
    /** @var NodeInterface[] $patches */
    $patches = $entity->get('patch')->getValue();
    $patch = count($patches) ? $patches[0] : [];
    foreach ($patch as $field_name => $value) {
      $field_patch_plugin = $entity->getPatchPluginFromOrigFieldName($field_name);
      $field_label = $entity->getOrigFieldLabel($field_name);
      $view[$field_name] = $field_patch_plugin->getFieldPatchView($field_label, $value);
    }
    $view['#attached']['library'][] = 'diff/diff.visual_inline';
    return $view;
  }

}
