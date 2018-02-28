<?php

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\patch_revision\Annotation\FieldPatchPlugin;
use Drupal\Core\Annotation\Translation;
use Drupal\patch_revision\Plugin\FieldPatchPluginBase;

/**
 * FieldPatchPlugin for field type image.
 *
 * @FieldPatchPlugin(
 *   id = "file",
 *   label = @Translation("FieldPatchPlugin for field type file"),
 *   field_types = {
 *     "file",
 *   },
 *   properties = {
 *     "target_id" = "",
 *     "display" = "1",
 *     "description" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchFile extends FieldPatchPluginBase {

  /**
   * @var EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'file';
  }

  /**
   * Returns the storage interface
   *
   * @return EntityStorageInterface|FALSE
   *   The storage.
   */
  protected function getEntityStorage() {
    if (!$this->entityStorage) {
      $this->entityStorage = \Drupal::service('entity_type.manager')->getStorage('file');
    }
    return $this->entityStorage ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function patchStringFormatter($property, $patch, $value_old) {
    $patch = json_decode($patch, true);
    $method = $this->camCase($property);
    $has_method = method_exists($this , $method);
    if (empty($patch)) {
      return [
        '#markup' => ($has_method) ? $this->{$method}($value_old) : $value_old,
      ];
    } else {
      $old = ($has_method) ? $this->{$method}($patch['old']) : $patch['old'];
      $new = ($has_method) ? $this->{$method}($patch['new']) : $patch['new'];
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
  protected function getTargetId($entity_id) {
    if (!$entity_id) {
      return $this->t('none');
    }
    /** @var File $entity */
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
  public function processPatchFieldValue($property, $value, $patch) {
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
      $method = $this->camCase($property);
      $has_method = method_exists($this , $method);
      $label = ($has_method) ? $this->{$method}($patch['old']) : $patch['old'];
      drupal_set_message($this->t('Expected old value for @property to be: @label', [
          '@label' => $label,
          '@property' => $property,
        ]), 'error');
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