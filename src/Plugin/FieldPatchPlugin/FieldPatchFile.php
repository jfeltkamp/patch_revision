<?php

namespace Drupal\change_requests\Plugin\FieldPatchPlugin;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\change_requests\Plugin\FieldPatchPluginBase;

/**
 * FieldPatchPlugin for field type image.
 *
 * @FieldPatchPlugin(
 *   id = "file",
 *   label = @Translation("FieldPatchPlugin for field type file"),
 *   fieldTypes = {
 *     "file",
 *   },
 *   properties = {
 *     "target_id" = {
 *       "label" = @Translation("Referred file"),
 *       "default_value" = "",
 *       "patch_type" = "full",
 *     },
 *     "display" = {
 *       "label" = @Translation("Display"),
 *       "default_value" = "1",
 *       "patch_type" = "full",
 *     },
 *     "description" = {
 *       "label" = @Translation("Description"),
 *       "default_value" = "",
 *       "patch_type" = "diff",
 *     },
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchFile extends FieldPatchPluginBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'file';
  }

  /**
   * Returns the storage interface.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|false
   *   The storage.
   */
  protected function getEntityStorage() {
    if (!$this->entityStorage) {
      $this->entityStorage = $this->entityTypeManager->getStorage('file');
    }
    return $this->entityStorage ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDiffDescription($str_src, $str_target) {
    return $this->diff->getTextDiff($str_src, $str_target);
  }

  /**
   * {@inheritdoc}
   */
  public function applyPatchDescription($value, $patch) {
    return $this->diff->applyPatchText($value, $patch, $this->t('description'));
  }

  /**
   * {@inheritdoc}
   */
  public function patchFormatterDescription($patch, $value_old) {
    return $this->diff->patchView($patch, $value_old);
  }

  /**
   * {@inheritdoc}
   */
  public function patchFormatterTargetId($patch, $value_old) {
    $patch = json_decode($patch, TRUE);
    if (empty($patch)) {
      return [
        '#markup' => $this->getTargetId($value_old),
      ];
    }
    else {
      $old = $this->getTargetId($patch['old']);
      $new = $this->getTargetId($patch['new']);
      return [
        '#markup' => $this->t('Old: <del>@old</del><br>New: <ins>@new</ins>', [
          '@old' => $old,
          '@new' => $new,
        ]),
      ];
    }
  }

  /**
   * Returns ready to use linked field label.
   *
   * @param int $entity_id
   *   The entity id.
   *
   * @return \Drupal\Core\GeneratedLink|\Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The label used for patch view.
   */
  protected function getTargetId($entity_id) {
    if (!$entity_id) {
      return $this->t('none');
    }
    /** @var \Drupal\file\Entity\File $entity */
    $entity = $this->getEntityStorage()->load((int) $entity_id);
    if (!$entity) {
      return $this->t('ID: @id was not found.', ['@id' => $entity_id]);
    }
    $name = $entity->getFileName();
    $url = Url::fromUri(file_create_url($entity->getFileUri()));
    $link = Link::fromTextAndUrl($name, $url)->toString();
    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function applyPatchTargetId($value, $patch) {
    $patch = json_decode($patch, TRUE);
    if (empty($patch)) {
      return [
        'result' => $value,
        'feedback' => [
          'code' => 100,
          'applied' => TRUE,
        ],
      ];
    }
    elseif (($patch['old'] !== $value) && ($patch['new'] !== $value)) {
      $label = $this->getTargetId($patch['old']);
      $message = $this->t('Expected old value for upload file to be: @label', [
        '@label' => $label,
      ]);
      return [
        'result' => $value,
        'feedback' => [
          'code' => 0,
          'applied' => FALSE,
          'message' => $message,
        ],
      ];
    }
    else {
      return [
        'result' => $patch['new'],
        'feedback' => [
          'code' => 100,
          'applied' => TRUE,
        ],
      ];
    }
  }

}
