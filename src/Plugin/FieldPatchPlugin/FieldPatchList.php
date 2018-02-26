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

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "list",
 *   label = @Translation("FieldPatchPlugin for all field types of numbers"),
 *   description = @Translation("Diff plugin for all texts with summary."),
 *   field_types = {
 *     "list_string",
 *     "list_float",
 *     "list_integer",
 *   },
 *   properties = {
 *     "value" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchList extends FieldPatchUndiffable {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'list';
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
  public function setWidgetFeedback(&$field, $feedback) {
    $item = 0;
    $applied = [];
    $code = [];
    $properties = array_keys($this->getFieldProperties());

    foreach ($feedback as $key => $col) {
      foreach ($properties as $property) {
        if(isset($col[$property]['applied'])) {
          if ($col[$property]['applied'] === FALSE) {
            $applied[] = FALSE;
            $field['#attributes']['class'][] = "pr-apply-{$property}-failed";
          }
        }
        if ($feedback[$item][$property]['code']) {
          $code[] = (int) $feedback[$item][$property]['code'];
        }
      }
    }

    $code = round(array_sum($code) / count($code));
    $message = (in_array(FALSE, $applied))
      ? $this->getMergeConflictMessage()
      : $this->getMergeSuccessMessage($code);

    $message_type = (!in_array(FALSE, $applied)) ? 'message' : 'error';
    $message_type = ($message_type !== 'error' && $code >= 99) ? $message_type : 'warning';

    if (isset($field['#type']) && $field['#type'] == 'container') {
      $field['patch_warn'] = [
        '#markup' => $message,
        '#weight' => -50,
        '#prefix' => "<strong class=\"pr-success-message $message_type\">",
        '#suffix' => "</strong>",
      ];
    }
  }

}