<?php

namespace Drupal\patch_revision;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\patch_revision\Plugin\FieldPatchPluginBase;
use Drupal\patch_revision\Plugin\FieldPatchPluginInterface;
use Drupal\patch_revision\Plugin\FieldPatchPluginManager;

/**
 * Class DiffService.
 */
class DiffService {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;


  /**
   * @var \Drupal\patch_revision\Plugin\FieldPatchPluginManager
   */
  private $plugin_manager;


  /**
   * Constructs a new DiffService object.
   *
   * @param EntityTypeManager $entity_type_manager
   * @param FieldPatchPluginManager $plugin_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager, FieldPatchPluginManager $plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->plugin_manager = $plugin_manager;
  }

  /**
   * Get a git-diff between two strings.
   *
   * @param $field_definition \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   * @param $str_src array
   *   The source array.
   * @param $str_target array
   *   The overridden array.
   *
   * @return array|FALSE
   *   The git diff.
   */
  public function getDiff($field_definition, $old, $new) {
    $plugin = $this->getPluginFromFieldType($field_definition->getType());
    if($plugin instanceof FieldPatchPluginInterface) {
      return $plugin->getFieldDiff($old, $new);
    }
    return FALSE;
  }

  /**
   * @param string $field_type
   *   The FieldType for what correct plugin is needed.
   *
   * @return \Drupal\patch_revision\Plugin\FieldPatchPluginBase|FALSE
   *   The FieldPatchPlugin belongs to FieldType.
   */
  public function getPluginFromFieldType($field_type) {
    switch($field_type) {
      case 'string':
      case 'string_long':
      case 'text':
      case 'text_long':
        $plugin = $this->plugin_manager->createInstance('default', ['field_type' => $field_type]);
        break;
      default:
        if ($this->plugin_manager->hasDefinition($field_type)) {
          $plugin = $this->plugin_manager->createInstance($field_type);
        } else {
          $plugin = FALSE;
        }
    }

    return $plugin;
  }

}

