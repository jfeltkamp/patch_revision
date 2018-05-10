<?php

namespace Drupal\change_requests\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\change_requests\DiffService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Field patch plugin plugins.
 */
abstract class FieldPatchPluginBase extends PluginBase implements FieldPatchPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Contains the conflict message.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|null
   */
  protected $mergeConflictMessage;

  /**
   * The std. Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The std. Drupal entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The change_requests module config.
   *
   * @var array
   */
  protected $moduleConfig;

  /**
   * The change_requests.diff service what is a diff_match_patch adapter.
   *
   * @var \Drupal\change_requests\DiffService
   */
  protected $diff;

  /**
   * A date formatter.
   *
   * @var \Drupal\change_requests\DiffService
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
    DateFormatter $date_formatter,
    FileUsageInterface $file_usage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->configFactory = $configFactory;
    $this->diff = $diff;
    $this->dateFormatter = $date_formatter;
    $this->fileUsatge = $file_usage;
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
      $container->get('change_requests.diff'),
      $container->get('date.formatter'),
      $container->get('file.usage')
    );
  }

  /**
   * Get module configs or the complete configuration.
   *
   * @param string|null $param
   *   The config parameter to return.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed|null
   *   Returns a given config or the complete module-config.
   */
  protected function getModuleConfig($param = NULL, $default = NULL) {
    if (!$this->moduleConfig) {
      $this->moduleConfig = $this->configFactory->get('change_requests.config');
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
   *
   */
  protected function registerUsage($fid) {
    // @Todo File usage integration. Here?!
    $usage = (int) $fid;
  }

  /**
   * {@inheritdoc}
   */
  public function getDiffTargetId($str_src, $str_target) {
    $this->registerUsage($str_target);
    return $this->getDiffDefault($str_src, $str_target);
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
   *   Returns a translated conflict message.
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
   *   Returns a translated success message.
   */
  protected function getMergeSuccessMessage($percent) {
    return $this->t('Field patch applied by %percent%.',
      [
        '%percent' => $percent,
      ]
    );
  }

  /**
   * Returns the specific field properties given in plugin definition.
   */
  public function getFieldProperties() {
    $plugin_definition = $this->getPluginDefinition();
    return ($plugin_definition['properties']);
  }

  /**
   * Merge all single feedback to one result.
   *
   * @param array $feedback
   *   The array with all field feedback.
   *
   * @return array
   *   Returns result array.
   */
  protected function mergeFeedback(array $feedback) {
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
   * Set the feedback inside the field widget.
   *
   * @param array $field
   *   Render array of field.
   * @param array $feedback
   *   The feedback array.
   */
  public function setWidgetFeedback(array &$field, array $feedback) {
    $result = $this->mergeFeedback($feedback);
    $this->setFeedbackClasses($field, $feedback);

    $message = ($result['applied'])
      ? $this->getMergeSuccessMessage($result['code'])
      : $this->getMergeConflictMessage();

    if (isset($field['#type']) && $field['#type'] == 'container') {
      $field['patch_result'] = [
        '#markup' => $message,
        '#weight' => -50,
        '#prefix' => "<strong class=\"cr-success-message {$result['type']}\">",
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
   * @param array $field
   *   The field render array.
   * @param array $feedback
   *   The summed and calculated feedback.
   */
  protected function setFeedbackClasses(array &$field, array $feedback) {
    $properties = array_keys($this->getFieldProperties());
    $item = 0;
    while (isset($field['widget'][$item])) {
      foreach ($properties as $property) {
        if (isset($feedback[$item][$property]['applied'])) {
          if ($feedback[$item][$property]['applied'] === FALSE) {
            if (isset($field['widget']['#cardinality']) && $field['widget']['#cardinality'] > 1) {
              $field['widget'][$item]['#attributes']['class'][] = "cr-apply-{$property}-failed";
            }
            else {
              $field['#attributes']['class'][] = "cr-apply-{$property}-failed";
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
  public function getFieldPatchView(array $values, FieldItemListInterface $field, $label = '') {
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
   * @param mixed $value
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
   * Some date don't come from $form_state->getValue() as written in database.
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
      // Strict means that the old value must be the same as the current.
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
   * Returns name for property getter if exists in context. Else returns false.
   *
   * @param string $prefix
   *   Prefix like "get" or "set".
   * @param string $property
   *   Property name.
   * @param string $separator
   *   Separator.
   * @param string $suffix
   *   Separator.
   *
   * @return string|false
   *   The getter name.
   */
  protected function methodName($prefix, $property, $separator = '_', $suffix = '') {
    $array = explode($separator, $property);
    $parts = array_map('ucwords', $array);
    $string = implode('', $parts);
    $suffix = ucfirst($suffix);
    $string = $prefix . $string . $suffix;
    return method_exists($this, $string) ? $string : FALSE;
  }

}
