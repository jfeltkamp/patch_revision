<?php

namespace Drupal\patch_revision\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Field patch plugin plugins.
 */
abstract class FieldPatchPluginBase extends PluginBase implements FieldPatchPluginInterface {


  // Add common methods and abstract methods for your plugin type here.

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'default';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return parent::getPluginDefinition();
  }

  protected function getFieldType() {

  }

  public function getFieldProperties() {
    $plugin_definition = $this->getPluginDefinition();
    return ($plugin_definition['properties']);
  }


  /**
   * {@inheritdoc}
   */
  public function getFieldDiff(array $old, array $new) {
    $result = [];
    $counts = max([count($old), count($new)]) - 1;
    for ($i = 0; $i <= $counts; $i++) {
      foreach($this->getFieldProperties() as $key => $default_value) {

        $str_source = isset($old[$i]) ? $old[$i][$key] : $default_value;
        $str_target = isset($new[$i]) ? $new[$i][$key] : $default_value;

        $result[$i][$key] = $this->processValueDiff($str_source, $str_target);
      }
    }
    return $result;
  }
}
