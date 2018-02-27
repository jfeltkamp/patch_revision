<?php
/**
 * Created by:
 * User: jfeltkamp
 * Date: 09.03.16
 * Time: 22:24
 */

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\patch_revision\Annotation\FieldPatchPlugin;
use Drupal\Core\Annotation\Translation;
use Drupal\patch_revision\Plugin\FieldPatchPluginBase;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "undiffable",
 *   label = @Translation("FieldPatchPlugin for all field types of numbers"),
 *   field_types = {
 *     "float",
 *     "integer",
 *     "decimal",
 *     "email",
 *     "telephone",
 *     "datetime",
 *     "timestamp",
 *   },
 *   properties = {
 *     "value" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchUndiffable extends FieldPatchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'undiffable';
  }

  /**
   * {@inheritdoc}
   */
  function patchStringFormatter($patch, $value_old) {
    $patch = json_decode($patch, true);
    if (empty($patch)) {
      return [
        '#markup' => $value_old
      ];
    } else {
      return [
        '#markup' => $this->t('Old: <del>@old</del><br>New: <ins>@new</ins>', [
          '@old' => $patch['old'],
          '@new' => $patch['new'],
        ])
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  function processPatchFieldValue($value, $patch) {
    $patch = json_decode($patch, true);
    if (empty($patch)) {
      return [
        'result' => $value,
        'feedback' => [
          'code' => 100,
          'applied' => TRUE
        ],
      ];
    } else {
      $code = ($patch['old'] === $value) ? 100 : 50;
      return [
        'result' => $patch['new'],
        'feedback' => [
          'code' => $code,
          'applied' => TRUE
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  function processValueDiff($str_src, $str_target) {
    if ($str_src === $str_target) {
      return json_encode([]);
    } else {
      return json_encode(['old' => $str_src, 'new' => $str_target]);
    }
  }

}