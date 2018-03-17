<?php

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\patch_revision\Plugin\FieldPatchPluginBase;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "text",
 *   label = @Translation("Improvements by diff."),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *   },
 *   properties = {
 *     "value" = {
 *       "label" = @Translation("Text"),
 *       "default_value" = "",
 *       "patch_type" = "diff",
 *     },
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchText extends FieldPatchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'text';
  }

  /**
   * {@inheritdoc}
   */
  public function getDiffValue($str_src, $str_target) {
    return $this->diff->getTextDiff($str_src, $str_target);
  }

  /**
   * {@inheritdoc}
   */
  public function applyPatchValue($value, $patch) {
    return $this->diff->applyPatchText($value, $patch, $this->t('text'));
  }

  /**
   * {@inheritdoc}
   */
  public function patchFormatterValue($patch, $value_old) {
    return $this->diff->patchView($patch, $value_old);
  }

}
