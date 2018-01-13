<?php

namespace Drupal\patch_revision\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Field patch plugin plugins.
 */
interface FieldPatchPluginInterface extends PluginInspectionInterface {


  // Add get/set methods for your plugin type here.

  /**
   * @param array $old
   *   Array with previous saved field data.
   * @param array $new
   *   Array with overwritten field data.
   *
   * @return mixed
   */
  function getFieldDiff(array $old, array $new);

  function patchField();

  function processValueDiff($str_src, $str_target);
}
