<?php

namespace Drupal\change_requests\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Field patch plugin item annotation object.
 *
 * @see \Drupal\change_requests\Plugin\FieldPatchPluginManager
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

  /**
   * The field types working with this plugin.
   *
   * @var array
   */
  public $fieldTypes = [];

  /**
   * The label of the plugin.
   *
   * Each property (like value, summary or target_id) is another array
   * with 2 attr:
   *   - "default_value": Set default ("") if not needed.
   *         Value to assume in Diff, when field is empty before/after merge.
   *   - "patch_type" (options: "full", "diff", "ref")
   *         Defines how to handle data in a patch.
   *     - full: Basic an valid in almost all cases.
   *     - diff: For texts. Create a diff from old and new text likely a
   *       unified diff.
   *     - ref: For referenced entities (usually for row 'target_id')
   *
   * @var array
   */
  public $properties = [];

}
