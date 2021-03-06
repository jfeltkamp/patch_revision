<?php

namespace Drupal\change_requests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

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
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\change_requests\Entity\Patch $entity */
    $view = parent::view($entity, $view_mode, $langcode);
    $original_entity = $entity->originalEntityRevision('origin');

    $header_data = $entity->getViewHeaderData();

    if ($header_data['orig_title']) {
      $view['#title'] = $this->t('Improvement for <em>@type: @title</em>', [
        '@type' => $header_data['orig_type'],
        '@title' => $header_data['orig_title'],
      ]);
    }
    else {
      $view['#title'] = $this->t('Display patch for node/@id.', [
        '@id' => $header_data['orig_id'],
      ]);
      drupal_set_message($this->t('The original entity, the patch refers to, could not be find.'), 'error');
    }

    $view['header'] = [
      '#theme' => 'cr_patch_header',
      '#created' => $header_data['created'],
      '#creator' => $header_data['creator'],
      '#log_message' => $header_data['log_message'],
      '#attached' => [
        'library' => ['change_requests/cr_patch_header'],
      ],
    ];

    // Build field patches views.
    /** @var \Drupal\node\NodeInterface[] $patches */
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
            'cr_field_view',
            'cr_field_view_name__' . $field_name,
            'cr_field_view_type__' . $field_type,
          ],
        ],
        'content' => $field_view,
      ];

    }
    $view['#attached']['library'][] = 'change_requests/cr_patch_view';
    return $view;
  }

}
