<?php

namespace Drupal\change_requests\Plugin;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Field patch plugin plugin manager.
 */
class FieldPatchPluginManager extends DefaultPluginManager {

  /**
   * The Drupal entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Constructs a new FieldPatchPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FieldPatchPlugin', $namespaces, $module_handler, 'Drupal\change_requests\Plugin\FieldPatchPluginInterface', 'Drupal\change_requests\Annotation\FieldPatchPlugin');

    $this->alterInfo('change_requests_field_patch_plugin_info');
    $this->setCacheBackend($cache_backend, 'change_requests_field_patch_plugin_plugins');
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
    $this->config = \Drupal::config('change_requests.config');
  }

  /**
   * Returns a list of patchable field types.
   *
   * @return array
   *   Array with all field types a FieldPatchPlugin exists.
   */
  public function getPatchableFieldTypes() {
    $plugins = $this->getDefinitions();
    $collector = [];
    foreach ($plugins as $plugin) {
      $collector = array_merge($collector, $plugin['fieldTypes']);
    }
    return $collector;
  }

  /**
   * Returns list of patchable fields and their definitions for a node type.
   *
   * @param string $node_type_id
   *   The node type id.
   * @param bool $bypass_explicit
   *   Bypass explicit check i.e. when form for explicit exclusion is build.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]|mixed
   *   List of field definitions.
   */
  public function getPatchableFields($node_type_id, $bypass_explicit = FALSE) {
    $fields = $this->entityFieldManager->getFieldDefinitions('node', $node_type_id);
    $patchable_field_types = $this->getPatchableFieldTypes();

    $general_excluded_fields = $this->config->get('general_excluded_fields');
    $explicit_excluded_fields = $this->config->get('bundle_' . $node_type_id . '_fields') ?: [];

    foreach ($fields as $name => $field) {
      /* @var $field \Drupal\Core\Field\FieldDefinitionInterface */
      $type = $field->getType();
      if (
        // NOT included because no field_type plugin exists.
        !in_array($type, $patchable_field_types)

        // IS excluded in general.
        || in_array($name, $general_excluded_fields)

        // IS NOT bypass AND IS explicit excluded fields.
        || (!$bypass_explicit && in_array($name, $explicit_excluded_fields, TRUE))
      ) {
        unset($fields[$name]);
      }

    }
    return $fields;
  }

  /**
   * Returns a plugin instance from field type.
   *
   * @param string $field_type
   *   The FieldType for what correct plugin is needed.
   * @param array $config
   *   The plugin configuration.
   *
   * @return \Drupal\change_requests\Plugin\FieldPatchPluginBase|false
   *   The FieldPatchPlugin belongs to FieldType.
   */
  public function getPluginFromFieldType($field_type, array $config = []) {
    $config = is_array($config) ? $config : [];
    $config = array_merge($config, ['field_type' => $field_type]);

    if ($this->hasDefinition($field_type)) {
      return $this->createInstance($field_type, $config);
    }
    else {
      foreach ($this->getDefinitions() as $key => $definition) {
        if (in_array($field_type, $definition['fieldTypes'])) {
          return $this->createInstance($key, $config);
        }
      }
      return FALSE;
    }
  }

  /**
   * Get a git-diff between two strings.
   *
   * @param string $field_type
   *   The field definition.
   * @param array $old
   *   The source array.
   * @param array $new
   *   The overridden array.
   *
   * @return array|false
   *   The git diff.
   */
  public function getDiff($field_type, array $old, array $new) {
    $plugin = $this->getPluginFromFieldType($field_type);
    if ($plugin instanceof FieldPatchPluginInterface) {
      return $plugin->getFieldDiff($old, $new);
    }
    return FALSE;
  }

  /**
   * Get a git-diff between two strings.
   *
   * @param string $field_type
   *   The field definition.
   * @param array $value
   *   The source array.
   * @param array $patch
   *   The overridden array.
   *
   * @return array|false
   *   The git diff.
   */
  public function patchField($field_type, array $value, array $patch) {
    $plugin = $this->getPluginFromFieldType($field_type);
    if ($plugin instanceof FieldPatchPluginInterface) {
      return $plugin->patchFieldValue($value, $patch);
    }
    return FALSE;
  }

}
