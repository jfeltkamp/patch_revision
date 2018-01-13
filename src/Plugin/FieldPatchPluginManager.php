<?php

namespace Drupal\patch_revision\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Field patch plugin plugin manager.
 */
class FieldPatchPluginManager extends DefaultPluginManager {


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
  }

}
