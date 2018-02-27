<?php

namespace Drupal\patch_revision;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

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
    $original_entity = $entity->originalEntityRevision('origin');

    if ($original_entity) {
      $nid = $original_entity->id();
      // Set page title.
      $url = Url::fromRoute('entity.node.canonical', ['node' => $nid]);
      $project_link = Link::fromTextAndUrl($entity->originalEntity()->label(), $url);
      $view['#title'] = $this->t('Improvement for <em>@type: @title</em>', [
        '@type' => $original_entity->type->entity->label(),
        '@title' => $project_link->toString()
      ]);
    } else {
      $view['#title'] = $this->t('Display patch for node/@id.', [
        '@id' => $entity->get('rid')->getString(),
      ]);
      drupal_set_message($this->t('The original entity, the patch refers to, could not be find.'), 'error');
    }

    // Set Creator view.
    $creator = $entity->getCreator();
    $view['creator'] =  $creator
      ? user_view($creator, 'compact')
      : ['#markup' => ''];

    // Set Log message.
    $view['message'] = [
      '#theme' => 'log_message',
      '#label' => $this->t('Log message by patch editor:'),
      '#message' => $entity->get('message')->getString() ?: '',
    ];

    // Build field patches views.
    /** @var NodeInterface[] $patches */
    $patch = $entity->getPatchField();
    foreach ($patch as $field_name => $value) {
      $field_type = $entity->getEntityFieldType($field_name);
      $original_field = $original_entity->get($field_name);
      $config = ($field_type == 'entity_reference')
        ? ['entity_type' => $original_field->getSetting('target_type')]
        : [];
      $field_patch_plugin = $entity->getPluginManager()->getPluginFromFieldType($field_type, $config);
      $field_view = ($field_patch_plugin) ? $field_patch_plugin->getFieldPatchView($value, $original_field) : [];
      $view[$field_name] = [
        '#type' => 'fieldset',
        '#title' => $entity->getOrigFieldLabel($field_name),
        '#open' => TRUE,
        '#attributes' => [
          'class' => [
            'pr_field_view',
            'pr_field_view_name__' . $field_name,
            'pr_field_view_type__' . $field_type,
          ]
        ],
        'content' => $field_view,
      ];

    }
    $view['#attached']['library'][] = 'patch_revision/patch_revision.patch_view';
    return $view;
  }

}
