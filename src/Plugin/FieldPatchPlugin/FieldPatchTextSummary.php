<?php
/**
 * Created by:
 * User: jfeltkamp
 * Date: 09.03.16
 * Time: 22:24
 */

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "text_with_summary",
 *   label = @Translation("FieldPatchPlugin for field type Texts with summary"),
 *   description = @Translation("Diff plugin for all texts with summary."),
 *   field_types = {
 *     "text_with_summary",
 *   },
 *   properties = {
 *     "summary" = "",
 *     "value" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchTextSummary extends FieldPatchDefault {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'text_with_summary';
  }

  /**
   * @param $field array
   * @param $feedback array
   */
  public function setWidgetFeedback(&$field, $feedback) {
    $item = 0;
    while (isset($field['widget'][$item])) {
      if(!$feedback[$item]['summary']['applied']) {
        if (isset($field['#type']) && $field['#type'] == 'container') {
          $field['patch_warn'] = [
            '#markup' => $this->getMergeConflictMessage(),
            '#weight' => -50,
          ];
        }
        if($field['widget']['#cardinality'] > 1) {
          $field['widget'][$item]['#attributes']['class'][] = 'patch-summary-failed';
        } else {
          $field['#attributes']['class'][] = 'patch-summary-failed';
        }
      }
      if(!$feedback[$item]['value']['applied']) {
        if (isset($field['#type']) && $field['#type'] == 'container') {
          $field['patch_warn'] = [
            '#markup' => $this->getMergeConflictMessage(),
            '#weight' => -50,
          ];
        }
        if($field['widget']['#cardinality'] > 1) {
          $field['widget'][$item]['#attributes']['class'][] = 'patch-value-failed';
        } else {
          $field['#attributes']['class'][] = 'patch-value-failed';
        }
      }
      $item++;
    }
  }
}