<?php

namespace Drupal\change_requests\Plugin\FieldPatchPlugin;

use Drupal\change_requests\Plugin\FieldPatchPluginBase;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "entity_reference",
 *   label = @Translation("FieldPatchPlugin for field type entity_reference"),
 *   fieldTypes = {
 *     "entity_reference",
 *   },
 *   properties = {
 *     "target_id" = {
 *       "label" = @Translation("Referred entity"),
 *       "default_value" = "",
 *       "patch_type" = "ref",
 *     },
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchReference extends FieldPatchPluginBase {

  /**
   * The store of the referred entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'entity_reference';
  }

  /**
   * Getter for entity_type property.
   *
   * @return string|bool
   *   Returns the entity type of the referred entity.
   */
  protected function getEntityType() {
    return $this->configuration['entity_type'] ?: FALSE;
  }

  /**
   * Returns the storage interface.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|false
   *   The storage.
   */
  protected function getEntityStorage() {
    $entity_type = $this->getEntityType();
    if ($entity_type && !$this->entityStorage) {
      $this->entityStorage = $this->entityTypeManager->getStorage($entity_type);
    }
    return $this->entityStorage ?: FALSE;
  }

  /**
   * Returns ready to use linked field label.
   *
   * @param int|mixed $entity_id
   *   The entity id.
   *
   * @throws \Exception
   *
   * @return \Drupal\Core\GeneratedLink|\Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The label used for patch view.
   */
  protected function getFormattedTargetId($entity_id) {
    if (!$entity_id) {
      return $this->t('none');
    }
    $entity = $this->getEntityStorage()->load($entity_id);
    if (!$entity) {
      return $this->t('ID: @id was not found.', ['@id' => $entity_id]);
    }
    return $entity->toLink(NULL, 'canonical', ['attributes' => ['target' => '_blank']])->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function applyPatchTargetId($value, $patch) {
    return parent::applyPatchDefault('target_id', $value, $patch, TRUE);
  }

}
