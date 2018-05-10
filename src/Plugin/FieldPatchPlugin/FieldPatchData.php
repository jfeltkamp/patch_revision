<?php

namespace Drupal\change_requests\Plugin\FieldPatchPlugin;

use Drupal\change_requests\Plugin\FieldPatchPluginBase;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "data",
 *   label = @Translation("FieldPatchPlugin for all field types of numbers"),
 *   fieldTypes = {
 *     "float",
 *     "integer",
 *     "decimal",
 *     "email",
 *     "telephone",
 *     "datetime",
 *     "timestamp",
 *   },
 *   properties = {
 *     "value" = {
 *       "label" = @Translation("Value"),
 *       "default_value" = "",
 *       "patch_type" = "full",
 *     },
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchData extends FieldPatchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'data';
  }

}
