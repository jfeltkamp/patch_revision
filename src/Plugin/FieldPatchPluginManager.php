<?php

namespace Drupal\patch_revision\Plugin;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Provides the Field patch plugin plugin manager.
 */
class FieldPatchPluginManager extends DefaultPluginManager {

  /**
   * @var EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * @var ImmutableConfig
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
    parent::__construct('Plugin/FieldPatchPlugin', $namespaces, $module_handler, 'Drupal\patch_revision\Plugin\FieldPatchPluginInterface', 'Drupal\patch_revision\Annotation\FieldPatchPlugin');

    $this->alterInfo('patch_revision_field_patch_plugin_info');
    $this->setCacheBackend($cache_backend, 'patch_revision_field_patch_plugin_plugins');
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
    $this->config = \Drupal::config('patch_revision.config');
  }


  /**
   * @return array
   *   Array with all field types a FieldPatchPlugin exists.
   */
  public function getPatchableFieldTypes() {

    $plugins = $this->getDefinitions();
    $collector = [];
    foreach ($plugins as $plugin) {
      $collector = array_merge($collector, $plugin['field_types']);
    }
    return $collector;
  }



  /**
   * @param NodeTypeInterface $node_type
   *    The node type.
   * @param bool $bypass_explicit
   *    Bypass explicit check i.e. when form for explicit exclusion is build.
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]|mixed
   */
  public function getPatchableFields($node_type, $bypass_explicit = FALSE) {
    $fields = $this->entityFieldManager->getFieldDefinitions('node', $node_type->id());
    $patchable_field_types = $this->getPatchableFieldTypes();

    $general_excluded_fields = $this->config->get('general_excluded_fields');
    $explicit_excluded_fields = $this->config->get('bundle_' . $node_type->id() . '_fields') ?: [];

    foreach ($fields as $name => $field) {
      /** @var $field \Drupal\Core\Field\FieldDefinitionInterface */
      $type = $field->getType();
      if (
        // NOT included because no field_type plugin exists.
        !in_array($type, $patchable_field_types)

        // IS excluded in general.
        || in_array($name, $general_excluded_fields)

        // IS NOT bypass AND IS explicit excluded fields.
        || (!$bypass_explicit && $explicit_excluded_fields[$name] === $name)
      ) {
        unset($fields[$name]);
      }

    }
    return $fields;
  }
}
