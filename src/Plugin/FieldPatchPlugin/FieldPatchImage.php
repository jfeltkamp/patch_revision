<?php

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
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
 *   id = "image",
 *   label = @Translation("FieldPatchPlugin for field type image"),
 *   field_types = {
 *     "image",
 *   },
 *   properties = {
 *     "target_id" = "",
 *     "alt" = "",
 *     "title" = "",
 *     "width" = "",
 *     "height" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchImage extends FieldPatchPluginBase {

  /**
   * @var EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'image';
  }

  /**
   * Returns the storage interface
   *
   * @return EntityStorageInterface|FALSE
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
  public function patchFormatter($property, $patch, $value_old) {
    $patch = json_decode($patch, true);
    $method = $this->getterName($property);
    if (empty($patch)) {
      return [
        '#markup' => ($method) ? $this->{$method}($value_old) : $value_old,
      ];
    } else {
      $old = ($method) ? $this->{$method}($patch['old']) : $patch['old'];
      $new = ($method) ? $this->{$method}($patch['new']) : $patch['new'];

      if ($property == 'target_id') {
        return [
          '#theme' => 'pr_view_image',
          '#left' => $old,
          '#right' => $new,
        ];
      } else {
        return [
          '#markup' => $this->t('Old: <del>@old</del><br>New: <ins>@new</ins>', [
            '@old' => $old,
            '@new' => $new,
          ])
        ];
      }
    }
  }

  /**
   * Returns ready to use linked field label.
   *
   * @param $entity_id
   *   The entity id.
   *
   * @return array|string
   *   The label used for patch view.
   */
  protected function getTargetId($entity_id) {
    if (!$entity_id) {
      return [
        '#type' => 'container',
        '#attributes' => ['class'=> ['pr-no-img']],
        'content' => ['#markup' => $this->t('No image')],
      ];
    }
    /** @var File $entity */
    $entity = $this->getEntityStorage()->load((int) $entity_id);
    if (!$entity) {
      return $this->t('ID: @id was not found.', ['@id' => $entity_id]);
    }

    $uri = $entity->getFileUri();
    $name = $entity->getFileName();
    $url = Url::fromUri(file_create_url($entity->getFileUri()));
    $link = Link::fromTextAndUrl($name, $url)->toRenderable();
    if ($uri) {
      $style = $this->getModuleConfig('image_style', 'thumbnail');
      return [
        '#type' => 'container',
        'image' => [
          '#theme' => 'image_style',
          '#style_name' => $style,
          '#uri' => $uri,
        ],
        'name' => $link,
        '#attached' => ['library' => ['patch_revision/patch_revision.pr-view-image']]
      ];
    } else {
      return [
        '#type' => 'container',
        '#attributes' => ['class'=> ['pr-no-img']],
        'content' => ['#markup' => $this->t('Image not found')],
      ];
    }
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
      $method = $this->getterName($property);
      $label = ($method) ? $this->{$method}($patch['old']) : $patch['old'];
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

  /**
   * {@inheritdoc}
   */
  public function prepareDataDb($values) {
    // Cloned code from Drupal\file\Plugin\Field\FieldWidget::massageFormValues.
    $new_values = [];
    foreach ($values as &$value) {
      foreach ($value['fids'] as $fid) {
        $new_value = $value;
        $new_value['target_id'] = $fid;
        unset($new_value['fids']);
        $new_values[] = $new_value;
      }
    }

    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public function validateDataIntegrity($value) {
    if (!is_array($value)) {
      return FALSE;
    }
    $properties = $this->getFieldProperties();
    return count(array_intersect_key($properties, $value)) == count($properties);
  }
}