<?php
/**
 * Created by:
 * User: jfeltkamp
 * Date: 09.03.16
 * Time: 22:24
 */

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\patch_revision\Annotation\FieldPatchPlugin;
use Drupal\Core\Annotation\Translation;
use Drupal\patch_revision\Plugin\FieldPatchPluginBase;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "entity_reference",
 *   label = @Translation("FieldPatchPlugin for field type entity_reference"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   properties = {
 *     "target_id" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchReference extends FieldPatchPluginBase {

  /**
   * @var EntityStorageInterface
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
   */
  protected function getEntityType() {
    return $this->configuration['entity_type'] ?: FALSE;
  }

  /**
   * Returns the storage interface
   *
   * @return EntityStorageInterface|FALSE
   *   The storage.
   */
  protected function getEntityStorage() {
    $entity_type = $this->getEntityType();
    if ($entity_type && !$this->entityStorage) {
      $this->entityStorage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    }
    return $this->entityStorage ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function patchStringFormatter($patch, $value_old) {
    $patch = json_decode($patch, true);
    if (empty($patch)) {
      return [
        '#markup' => $this->getEntityLabel((int) $value_old)
      ];
    } else {
      $old = $this->getEntityLabel((int) $patch['old']);
      $new = $this->getEntityLabel((int) $patch['new']);
      return [
        '#markup' => $this->t('Old: <del>@old</del><br>New: <ins>@new</ins>', [
          '@old' => $old,
          '@new' => $new,
        ])
      ];
    }
  }

  /**
   * Returns ready to use linked field label.
   *
   * @param $entity_id
   *   The entity id.
   *
   * @return \Drupal\Core\GeneratedLink|\Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The label used for patch view.
   */
  protected function getEntityLabel($entity_id) {
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
  public function processPatchFieldValue($value, $patch) {
    $patch = json_decode($patch, true);
    if (empty($patch)) {
      return [
        'result' => $value,
        'feedback' => [
          'code' => 100,
          'applied' => TRUE
        ],
      ];
    } elseif ($patch['old'] != $value) {
      $label = $this->getEntityLabel((int) $patch['old']);
      drupal_set_message($this->t('Expected old value to be: @label', ['@label' => $label]), 'error');
      return [
        'result' => $value,
        'feedback' => [
          'code' => 0,
          'applied' => FALSE,
        ],
      ];
    } else {
      return [
        'result' => $patch['new'],
        'feedback' => [
          'code' => 100,
          'applied' => TRUE
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processValueDiff($str_src, $str_target) {
    if ($str_src === $str_target) {
      return json_encode([]);
    } else {
      return json_encode(['old' => $str_src, 'new' => $str_target]);
    }
  }

}