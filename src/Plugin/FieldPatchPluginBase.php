<?php

namespace Drupal\patch_revision\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Field patch plugin plugins.
 */
abstract class FieldPatchPluginBase extends PluginBase implements FieldPatchPluginInterface {


  protected function getFieldType() {}


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

  /**
   * {@inheritdoc}
   */
  public function patchFieldValue($value, $patch) {
    $result = [];
    $counts = max([count($value), count($patch)]) - 1;
    for ($i = 0; $i <= $counts; $i++) {
      foreach($this->getFieldProperties() as $key => $default_value) {

        $value_item = isset($value[$i]) ? $value[$i][$key] : $default_value;
        $patch_item = isset($patch[$i]) ? $patch[$i][$key] : FALSE;

        $result_container = $this->processPatchFieldValue($value_item, $patch_item);

        $result['result'][$i][$key] = $result_container['result'];
        $result['feedback'][$i][$key] = $result_container['feedback'];
      }
    }
    return $result;
  }

  /**
   *
   */
  public function getFieldPatchView($field_name, $values) {
    $result = [
      '#theme' => 'field_patches',
      '#title' => $field_name,
      '#items' => [],
    ];
    foreach ($values as $item => $value) {
      $result['#items']["item_{$field_name}"] = [];
      foreach($this->getFieldProperties() as $key => $default_value) {
        $result['#items'][$item][$key] = [
          '#theme' => 'field_patch',
          '#col' => $key,
          '#patch' => $this->patchStringFormatter($value[$key]),
        ];
      }
    }
    return $result;
  }


  /**
   * Creates a temporary file with content and returns the file handle.
   *
   * @param string $content
   *   The content to apply to the temporary file.
   *
   * @return bool|resource
   *   Returns the file handle or false.
   */
  protected function createTempFile($content = null) {
    $tmpFile = tmpfile();
    if ($content) {
      $metaData = stream_get_meta_data($tmpFile);
      file_put_contents($metaData['uri'], $content);
    }
    // xdiff
    return $tmpFile;
  }

  /**
   * Creates a temporary file with content and returns the file handle.
   *
   * @param string $content
   *   The content to apply to the temporary file.
   *
   * @return bool|resource
   *   Returns the file handle or false.
   */
  protected function createNamTempFile($name, $content = null) {
    $tmpFile = tempnam(sys_get_temp_dir(), $name);
    $handle = fopen($tmpFile, "rw");
    if ($content) {
      $metaData = stream_get_meta_data($handle);
      file_put_contents($metaData['uri'], $content);
    }
    // xdiff
    return $handle;
  }

}
