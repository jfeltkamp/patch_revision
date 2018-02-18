<?php

namespace Drupal\patch_revision\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Base class for Field patch plugin plugins.
 */
abstract class FieldPatchPluginBase extends PluginBase implements FieldPatchPluginInterface {

  /**
   * @var TranslatableMarkup|NULL
   */
  protected $mergeConflictMessage;


  protected function getFieldType() {}

  /**
   * Get the conflict message.
   *
   * @return TranslatableMarkup
   */
  protected function getMergeConflictMessage() {
    if (!$this->mergeConflictMessage) {
      $this->mergeConflictMessage =
        new TranslatableMarkup('<strong class="pr-success-message">Field has merge conflicts, please edit manually.</strong>');
    }
    return $this->mergeConflictMessage;
  }

  /**
   * Get the conflict message.
   *
   * @return TranslatableMarkup
   */
  protected function getMergeSuccessMessage($percent) {
    return new TranslatableMarkup('Field patch applied by %percent%.',
      [
        '%percent' => $percent,
      ]
    );
  }

  public function getFieldProperties() {
    $plugin_definition = $this->getPluginDefinition();
    return ($plugin_definition['properties']);
  }

  /**
   * @param $field array
   * @param $feedback array
   */
  public function setWidgetFeedback(&$field, $feedback) {
    $item = 0;
    while (isset($field['widget'][$item])) {
      if(!$feedback[$item]['value']['applied']) {
        if (isset($field['#type']) && $field['#type'] == 'container') {
          array_unshift($field, ['#markup' => $this->getMergeConflictMessage()] );
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
   * {@inheritdoc}
   */
  public function getFieldPatchView($values, $field, $label = '') {
    $result = [
      '#theme' => 'field_patches',
      '#title' => $label,
      '#items' => [],
    ];
    $field_value = $field->getValue();
    foreach ($values as $item => $value) {
      $result['#items']["item_{$field->getName()}"] = [];
      foreach($this->getFieldProperties() as $key => $default_value) {
        $result['#items'][$item][$key] = [
          '#theme' => 'field_patch',
          '#col' => $key,
          '#patch' => $this->patchStringFormatter($value[$key], $field_value[$item][$key]),
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
    return $handle;
  }

}
