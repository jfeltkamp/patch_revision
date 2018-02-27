<?php

namespace Drupal\patch_revision\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Field patch plugin item annotation object.
 *
 * @see \Drupal\patch_revision\Plugin\FieldPatchPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class FieldPatchPlugin extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
