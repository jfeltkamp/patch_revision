<?php

namespace Drupal\change_requests\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines an interface for Field patch plugin plugins.
 */
interface FieldPatchPluginInterface extends PluginInspectionInterface {

  /**
   * Get the plugin id.
   *
   * @return string
   *   The id of the plugin.
   */
  public function getPluginId();

  /**
   * Get a field diff (using output of changed_field API as base).
   *
   * @param array $old
   *   Array with previous saved field data.
   * @param array $new
   *   Array with overwritten field data.
   *
   * @return mixed
   *   Returns a plugin specific diff.
   */
  public function getFieldDiff(array $old, array $new);

  /**
   * Apply patch to an field value.
   *
   * @param string $key
   *   The data column to work on.
   * @param mixed $value
   *   Array with previous saved field data.
   * @param mixed $patch
   *   Array with overwritten field data.
   * @param bool $strict
   *   If true check matching old and current value or ignore if false.
   *
   * @return mixed
   *   Returns patched value.
   */
  public function applyPatchDefault($key, $value, $patch, $strict);

  /**
   * Main feature that process the diff command and returns the patch.
   *
   * @param mixed $str_src
   *   Original value.
   * @param mixed $str_target
   *   Overwritten value.
   *
   * @return mixed
   *   The diff created from the input values.
   */
  public function getDiffDefault($str_src, $str_target);

  /**
   * Returns a render array with formatted markup.
   *
   * @param string $key
   *   The data column to work on.
   * @param string $patch
   *   The patch to apply.
   * @param string $value_old
   *   The old value to apply patch on.
   *
   * @return array
   *   The patch result array
   */
  public function patchFormatterDefault($key, $patch, $value_old);

  /**
   * Returns a formatted view for the complete Patch.
   *
   * @param array $patch_value
   *   The patch for this field.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   FieldItemList .
   *
   * @return mixed
   *   Returns a renderable patch view for field.
   */
  public function getFieldPatchView(array $patch_value, FieldItemListInterface $field);

}
