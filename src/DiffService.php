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


}

