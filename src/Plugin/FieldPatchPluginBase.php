<?php

namespace Drupal\patch_revision\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\patch_revision\DiffService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Field patch plugin plugins.
 */
abstract class FieldPatchPluginBase extends PluginBase implements FieldPatchPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|null
   */
  protected $mergeConflictMessage;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * @var array
   */
  protected $moduleConfig;

  /**
   * @var \Drupal\patch_revision\DiffService
   */
  protected $diff;

  /**
   * @var \Drupal\patch_revision\DiffService
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entityTypeManager,
    EntityFieldManager $entityFieldManager,
    ConfigFactory $configFactory,
    DiffService $diff,
    DateFormatter $date_formatter
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->configFactory = $configFactory;
    $this->diff = $diff;
    $this->dateFormatter = $date_formatter;
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
      $container->get('config.factory'),
      $container->get('patch_revision.diff'),
      $container->get('date.formatter')
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
    }
    else {
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
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
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
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function getMergeSuccessMessage($percent) {
    return $this->t('Field patch applied by %percent%.',
      [
        '%percent' => $percent,
      ]
    );
  }

  /**
   *
   */
  public function getFieldProperties() {
    $plugin_definition = $this->getPluginDefinition();
    return ($plugin_definition['properties']);
  }

  /**
   *
   */
  protected function mergeFeedback($feedback) {
    $applied = [];
    $code = [];
    $messages = [];
    foreach ($feedback as $fb) {
      foreach ($fb as $property => $result) {
        $applied[] = $result['applied'];
        $code[] = $result['code'];
        if (isset($result['message'])) {
          $messages[] = $result['message'];
        }
      }
    }
    $code = round(array_sum($code) / count($code));
    $applied = (!in_array(FALSE, $applied));
    $type = (!$applied) ? 'error' : (($code < 100) ? 'warning' : 'message');

    return [
      'code' => $code,
      'applied' => $applied,
      'type' => $type,
      'messages' => $messages,
    ];
  }

  /**
   * @param array $field
   * @param array $feedback
   */
  public function setWidgetFeedback(&$field, $feedback) {
    $result = $this->mergeFeedback($feedback);
    $this->setFeedbackClasses($field, $feedback);

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

      if (!empty($result['messages'])) {
        $field['patch_messages'] = [
          '#type' => 'container',
          '#weight' => -45,
          '#attributes' => [
            'class' => [
              'messages',
              "messages--{$result['type']}",
            ],
          ],
        ];
        foreach ($result['messages'] as $key => $message) {
          $field['patch_messages'][$key] = [
            '#markup' => $message,
            '#prefix' => '<div>',
            '#suffix' => '</div>',
          ];
        }
      }
    }
  }

  /**
   * Set classes to widget to get highlighted the conflicting field items.
   *
   * @param $field
   *   The field render array.
   *
   * @param $feedback
   *   Te summed and calculated feedback.
   */
  protected function setFeedbackClasses(&$field, $feedback) {
    $properties = array_keys($this->getFieldProperties());
    $item = 0;
    while (isset($field['widget'][$item])) {
      foreach ($properties as $property) {
        if (isset($feedback[$item][$property]['applied'])) {
          if ($feedback[$item][$property]['applied'] === FALSE) {
            if ($field['widget']['#cardinality'] > 1) {
              $field['widget'][$item]['#attributes']['class'][] = "pr-apply-{$property}-failed";
            }
            else {
              $field['#attributes']['class'][] = "pr-apply-{$property}-failed";
            }
          }
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
      foreach ($this->getFieldProperties() as $key => $definition) {

        $str_source = isset($old[$i][$key]) ? $old[$i][$key] : $definition['default_value'];
        $str_target = isset($new[$i][$key]) ? $new[$i][$key] : $definition['default_value'];

        $result[$i][$key] = ($method_name = $this->methodName('getDiff', $key))
          ? $this->{$method_name}($str_source, $str_target)
          : $this->getDiffDefault($str_source, $str_target);

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
      foreach ($this->getFieldProperties() as $key => $definition) {
        $value_item = isset($value[$i]) ? $value[$i][$key] : $definition['default_value'];
        $patch_item = isset($patch[$i]) ? $patch[$i][$key] : FALSE;

        $result_container = ($method_name = $this->methodName('applyPatch', $key))
          ? $this->{$method_name}($value_item, $patch_item)
          : $this->applyPatchDefault($key, $value_item, $patch_item);

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
      foreach ($this->getFieldProperties() as $key => $definition) {
        $old_value = isset($field_value[$item][$key])
          ? $field_value[$item][$key]
          : $definition['default_value'];

        $patch = ($method_name = $this->methodName('patchFormatter', $key))
          ? $this->{$method_name}($value[$key], $old_value)
          : $this->patchFormatterDefault($key, $value[$key], $old_value);

        $result['#items'][$item][$key] = [
          '#theme' => 'field_patch',
          '#col' => ['#markup' => "<b>{$definition['label']}</b>"],
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
   * {@inheritdoc}
   */
  public function getDiffDefault($str_src, $str_target) {
    if ($str_src === $str_target) {
      return json_encode([]);
    }
    else {
      return json_encode(['old' => $str_src, 'new' => $str_target]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function patchFormatterDefault($key, $patch, $value_old) {
    $patch = json_decode($patch, TRUE);
    $value_formatter = $this->methodName('getFormatted', $key);
    if (empty($patch)) {
      return [
        '#markup' => ($value_formatter) ? $this->{$value_formatter}($value_old) : $value_old,
      ];
    }
    else {
      return [
        '#markup' => $this->t('Old: <del>@old</del><br>New: <ins>@new</ins>', [
          '@old' => ($value_formatter) ? $this->{$value_formatter}($patch['old']) : $patch['old'],
          '@new' => ($value_formatter) ? $this->{$value_formatter}($patch['new']) : $patch['new'],
        ]),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applyPatchDefault($key, $value, $patch, $strict = FALSE) {
    $patch = json_decode($patch, TRUE);
    $value_formatter = $this->methodName('getFormatted', $key);

    if (empty($patch)) {
      return [
        'result' => $value,
        'feedback' => [
          'code' => 100,
          'applied' => TRUE,
        ],
      ];
    }
    elseif ($strict && ($patch['old'] !== $value) && ($patch['new'] !== $value)) {
      // Strict means that the old value (to be removed) must be the same as the current.
      // Except the case that the new value is already set.
      $message = $this->t('Expected old value to be "@expected" but found "@found".', [
        '@expected' => ($value_formatter) ? $this->{$value_formatter}($patch['old']) : $patch['old'],
        '@found' => ($value_formatter) ? $this->{$value_formatter}($value) : $value,
      ]);
      return [
        'result' => $value,
        'feedback' => [
          'code' => 0,
          'applied' => FALSE,
          'message' => $message,
        ],
      ];
    }
    else {
      $code = (($patch['old'] !== $value) && ($patch['new'] !== $value)) ? 50 : 100;
      $result = [
        'result' => $patch['new'],
        'feedback' => [
          'code' => $code,
          'applied' => TRUE,
        ],
      ];
      if ($code == 50) {
        $result['feedback']['message'] = $this->t('Expected old value to be "@expected" but found "@found".', [
          '@expected' => ($value_formatter) ? $this->{$value_formatter}($patch['old']) : $patch['old'],
          '@found' => ($value_formatter) ? $this->{$value_formatter}($value) : $value,
        ]);
      }
      return $result;
    }
  }

  /**
   * Returns name for a getter of properties if exists in self context, else returns false.
   *
   * @param $property
   *   Property name.
   * @param string $separator
   *   Separator.
   * @param string $prefix
   *   Prefix like "get" or "set".
   * @param string $suffix
   *   Separator.
   *
   * @return string|false
   *   The getter name.
   */
  protected function methodName($prefix = 'get', $property, $separator = '_', $suffix = '') {
    $array = explode($separator, $property);
    $parts = array_map('ucwords', $array);
    $string = implode('', $parts);
    $suffix = ucfirst($suffix);
    $string = $prefix . $string . $suffix;
    return method_exists($this, $string) ? $string : FALSE;
  }

}
