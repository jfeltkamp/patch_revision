<?php

namespace Drupal\patch_revision\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Field patch plugin plugins.
 */
interface FieldPatchPluginInterface extends PluginInspectionInterface {


  // Add get/set methods for your plugin type here.

  /**
   * Get a field diff (using output of changed_field API as base).
   *
   * @param array $old
   *   Array with previous saved field data.
   * @param array $new
   *   Array with overwritten field data.
   *
   * @return mixed
   */
  function getFieldDiff(array $old, array $new);

  /**
   * Apply patch to an field
   *
   * @return mixed
   */
  function patchField();

  function processValueDiff($str_src, $str_target);

  /**
   * Returns a formatted view for the complete Patch.
   *
   * @param $field_name
   *   Field name
   * @param $value
   *
   * @return mixed
   */
  function getFieldPatchView($field_name, $value);


  /**
   * Returns a render array with formatted markup.
   *
   * @param $string
   * @return array
   */
  function patchStringFormatter($string);
}
