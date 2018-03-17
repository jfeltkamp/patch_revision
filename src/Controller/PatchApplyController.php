<?php

namespace Drupal\patch_revision\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\patch_revision\Entity\Patch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\patch_revision\DiffService;
use Drupal\patch_revision\Plugin\FieldPatchPluginManager;

/**
 * Class PatchApplyController.
 */
class PatchApplyController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\patch_revision\DiffService definition.
   *
   * @var \Drupal\patch_revision\DiffService
   */
  protected $patchRevisionDiff;

  /**
   * Drupal\patch_revision\Plugin\FieldPatchPluginManager definition.
   *
   * @var \Drupal\patch_revision\Plugin\FieldPatchPluginManager
   */
  protected $pluginManagerFieldPatchPlugin;

  /**
   * Drupal\patch_revision\Entity\Patch definition.
   *
   * @var \Drupal\patch_revision\Entity\Patch|false
   */
  protected $patch;

  /**
   * Drupal\node\NodeInterface definition.
   *
   * @var \Drupal\node\NodeInterface|false
   */
  protected $node;

  /**
   * Constructs a new PatchApplyController object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, DiffService $patch_revision_diff, FieldPatchPluginManager $plugin_manager_field_patch_plugin) {
    $this->entityTypeManager = $entity_type_manager;
    $this->patchRevisionDiff = $patch_revision_diff;
    $this->pluginManagerFieldPatchPlugin = $plugin_manager_field_patch_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('patch_revision.diff'),
      $container->get('plugin.manager.field_patch_plugin')
    );
  }

  /**
   * Apply patch.
   *
   * @param int $patch
   *   The patch ID.
   *
   * @return array
   *   Rendered .
   */
  public function apply($patch) {
    // Set patch or die.
    $this->patch = $this->entityTypeManager->getStorage('patch')->load($patch);
    if (!$this->patch) {
      drupal_set_message($this->t('Patch with ID: @id could not be found.', ['@id' => $patch]), 'warning');
      return [];
    }
    $this->node = $this->patch->originalEntity();
    if (!$this->node) {
      drupal_set_message($this->t('The original node for this patch does not exist anymore.'), 'warning');
      return [];
    }

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: apply with parameter(s): $patch'),
    ];
  }

}
