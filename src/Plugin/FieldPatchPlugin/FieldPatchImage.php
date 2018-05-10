<?php

namespace Drupal\change_requests\Plugin\FieldPatchPlugin;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\change_requests\Plugin\FieldPatchPluginBase;

/**
 * FieldPatchPlugin for field type image.
 *
 * @FieldPatchPlugin(
 *   id = "image",
 *   label = @Translation("FieldPatchPlugin for field type image"),
 *   fieldTypes = {
 *     "image",
 *   },
 *   properties = {
 *     "target_id" = {
 *       "label" = @Translation("Referred image"),
 *       "default_value" = "",
 *       "patch_type" = "full",
 *     },
 *     "alt" = {
 *       "label" = @Translation("Alternative text"),
 *       "default_value" = "",
 *       "patch_type" = "diff",
 *     },
 *     "title" = {
 *       "label" = @Translation("Title"),
 *       "default_value" = "",
 *       "patch_type" = "diff",
 *     },
 *     "width" = {
 *       "label" = @Translation("Width"),
 *       "default_value" = "",
 *       "patch_type" = "full",
 *     },
 *     "height" = {
 *       "label" = @Translation("Height"),
 *       "default_value" = "",
 *       "patch_type" = "full",
 *     },
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchImage extends FieldPatchPluginBase {

  /**
   * The file storage used for image fields.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'image';
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
  public function getDiffAlt($str_src, $str_target) {
    return $this->diff->getTextDiff($str_src, $str_target);
  }

  /**
   * {@inheritdoc}
   */
  public function applyPatchAlt($value, $patch) {
    return $this->diff->applyPatchText($value, $patch, $this->t('alternative text'));
  }

  /**
   * {@inheritdoc}
   */
  public function patchFormatterAlt($patch, $value_old) {
    return $this->diff->patchView($patch, $value_old);
  }

  /**
   * {@inheritdoc}
   */
  public function getDiffTitle($str_src, $str_target) {
    return $this->diff->getTextDiff($str_src, $str_target);
  }

  /**
   * {@inheritdoc}
   */
  public function applyPatchTitle($value, $patch) {
    return $this->diff->applyPatchText($value, $patch, $this->t('title'));
  }

  /**
   * {@inheritdoc}
   */
  public function patchFormatterTitle($patch, $value_old) {
    return $this->diff->patchView($patch, $value_old);
  }

  /**
   * {@inheritdoc}
   */
  public function patchFormatterTargetId($patch, $value_old) {
    $patch = json_decode($patch, TRUE);
    if (empty($patch)) {
      return [
        '#theme' => 'cr_view_image',
        '#center' => $this->getTargetId($value_old),
      ];
    }
    else {
      $old = $this->getTargetId($patch['old']);
      $new = $this->getTargetId($patch['new']);

      return [
        '#theme' => 'cr_view_image',
        '#left' => $old,
        '#right' => $new,
      ];
    }
  }

  /**
   * Returns ready to use linked field label.
   *
   * @param int $entity_id
   *   The entity id.
   *
   * @return array|string
   *   The label used for patch view.
   */
  protected function getTargetId($entity_id) {
    if (!$entity_id) {
      return [
        '#type' => 'container',
        '#attributes' => ['class' => ['cr-no-img']],
        'content' => ['#markup' => $this->t('No image')],
      ];
    }
    /** @var \Drupal\file\Entity\File $entity */
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
        '#attached' => ['library' => ['change_requests/cr_view_image']],
      ];
    }
    else {
      return [
        '#type' => 'container',
        '#attributes' => ['class' => ['cr-no-img']],
        'content' => ['#markup' => $this->t('Image not found')],
      ];
    }
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
      $message = $this->t('Expected old value for image to be: @label', [
        '@label' => $patch['old'],
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
