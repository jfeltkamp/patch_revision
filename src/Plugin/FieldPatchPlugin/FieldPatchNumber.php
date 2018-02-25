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
 *   id = "number",
 *   label = @Translation("FieldPatchPlugin for all field types of numbers"),
 *   description = @Translation("Diff plugin for all texts with summary."),
 *   field_types = {
 *     "float",
 *     "integer",
 *     "decimal",
 *   },
 *   properties = {
 *     "value" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchNumber extends FieldPatchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'number';
  }

  /**
   * {@inheritdoc}
   */
  function patchStringFormatter($patch, $value_old) {
    if (isset($patch['const'])) {
      return [
        '#markup' => $this->t('No changes. (Value was @value)', [
          '@value' => $patch['const'],
        ])
      ];
    } else {
      return [
        '#markup' => $this->t('Old value: <del>@old</del><br>New value: <ins>@new</ins>', [
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
    if (isset($patch['const'])) {
      $code = ($patch['const'] === $value) ? 100 : 90;
      return [
        'result' => $value,
        'feedback' => [
          'code' => $code,
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
      return [
        'const' => $str_src
      ];
    } else {
      return [
       'old' => $str_src,
       'new' => $str_target,
      ];
    }
  }

}