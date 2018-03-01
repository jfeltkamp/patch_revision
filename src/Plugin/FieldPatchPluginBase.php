<?php

namespace Drupal\patch_revision\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Field patch plugin plugins.
 */
abstract class FieldPatchPluginBase extends PluginBase implements FieldPatchPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * @var TranslatableMarkup|NULL
   */
  protected $mergeConflictMessage;

  /**
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * @var array
   */
  protected $moduleConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entityTypeManager,
    EntityFieldManager $entityFieldManager,
    ConfigFactory $configFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * @param string|null $param
   *   The config parameter to return.
   * @param mixed $default
   *   The default value .
   * @return mixed|null
   */
  protected function getModuleConfig($param = NULL, $default = NULL) {
    if (!$this->moduleConfig) {
      $this->moduleConfig = $this->configFactory->get('patch_revision.config');
    }
    if (!$param) {
      return $this->moduleConfig;
    } else {
      return ($value = $this->moduleConfig->get($param))
        ? $value
        : $default;
    }
  }

  /**
   * Returns current field type.
   *
   * @return mixed
   *   The field type.
   */
  protected function getFieldType() {
    return $this->configuration['field_type'];
  }

  /**
   * Get the conflict message.
   *
   * @return TranslatableMarkup
   */
  protected function getMergeConflictMessage() {
    if (!$this->mergeConflictMessage) {
      $this->mergeConflictMessage =
        $this->t('Field has merge conflicts, please edit manually.');
    }
    return $this->mergeConflictMessage;
  }

  /**
   * Get the conflict message.
   *
   * @return TranslatableMarkup
   */
  protected function getMergeSuccessMessage($percent) {
    return $this->t('Field patch applied by %percent%.',
      [
        '%percent' => $percent,
      ]
    );
  }

  public function getFieldProperties() {
    $plugin_definition = $this->getPluginDefinition();
    return ($plugin_definition['properties']);
  }


  protected function mergeFeedback($feedback) {
    $applied = [];
    $code = [];
    $messages = [];
    foreach ($feedback as $fb) {
      foreach ($fb as $property => $result) {
        $applied[] = $result['applied'];
        $code[] = $result['code'];
        if (isset($result['message'])) { $messages[] = $result['message']; }
      }
    }
    $code = round(array_sum($code) / count($code));
    $applied = (!in_array(FALSE, $applied));
    if (!$applied) { $type = 'error'; }
    elseif($code < 100) {  $type = 'warning'; }
    else { $type = 'message'; }
    return [
      'code' => $code,
      'applied' => $applied,
      'type' => $type,
      'messages' => $messages,
    ];
  }

  /**
   * @param $field array
   * @param $feedback array
   */
  public function setWidgetFeedback(&$field, $feedback) {
    $item = 0;
    $result = $this->mergeFeedback($feedback);
    $properties = array_keys($this->getFieldProperties());

    while (isset($field['widget'][$item])) {
      foreach ($properties as $property) {
        if(isset($feedback[$item][$property]['applied'])) {
          if ($feedback[$item][$property]['applied'] === FALSE) {
            if($field['widget']['#cardinality'] > 1) {
              $field['widget'][$item]['#attributes']['class'][] = "pr-apply-{$property}-failed";
            } else {
              $field['#attributes']['class'][] = "pr-apply-{$property}-failed";
            }
          }
        }
      }
      $item++;
    }

    $message = ($result['applied'])
      ? $this->getMergeSuccessMessage($result['code'])
      : $this->getMergeConflictMessage();

    if (isset($field['#type']) && $field['#type'] == 'container') {
      $field['patch_result'] = [
        '#markup' => $message,
        '#weight' => -50,
        '#prefix' => "<strong class=\"pr-success-message {$result['type']}\">",
        '#suffix' => "</strong>",
      ];
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

        $str_source = isset($old[$i][$key]) ? $old[$i][$key] : $default_value;
        $str_target = isset($new[$i][$key]) ? $new[$i][$key] : $default_value;

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

        $result_container = $this->processPatchFieldValue($key, $value_item, $patch_item);

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
        $old_value = isset($field_value[$item][$key]) ? $field_value[$item][$key] : $default_value;
        $patch = $this->patchFormatter($key, $value[$key], $old_value);
        $result['#items'][$item][$key] = [
          '#theme' => 'field_patch',
          '#col' => $key,
          '#patch' => $patch,
        ];
      }
    }
    return $result;
  }

  /**
   * Data integrity test before writing data to entity.
   *
   * @param $value
   *   The value from patch entity to write into original entity.
   *
   * @return bool
   *   If data integrity test is valid.
   */
  public function validateDataIntegrity($value) {
    if (!is_array($value)) {
      return FALSE;
    }
    $properties = $this->getFieldProperties();
    return count(array_intersect_key($properties, $value)) == count($properties);
  }

  /**
   * Some date don't come from $form_state->getValue() in as they are used to write in database.
   *
   * @param mixed $data
   *   Data as they are received from $form_state object.
   *
   * @return mixed
   *   Data writable to database.
   */
  public function prepareDataDb($data) {
    return $data;
  }

  /**
   * Returns name for a getter of properties if exists in self context, else returns false.
   *
   * @param $property
   *   Property name.
   * @param string $separator
   *   Separator.
   *
   * @return string|FALSE
   *   The getter name.
   */
  protected function getterName($property, $separator = '_') {
    $array = explode($separator, $property);
    $parts = array_map('ucwords', $array);
    $string = implode('', $parts);
    $string = 'get'.$string;
    return method_exists($this, $string) ? $string : FALSE;
  }

}
