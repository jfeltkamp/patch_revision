<?php

namespace Drupal\change_requests\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class PatchAjaxController.
 */
class PatchAjaxController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PatchAjaxController object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get the rendered patch.
   *
   * @param $patch
   *   The patch id to be shown in modal.
   * @param string $view_mode
   *   The view mode to render the patch.
   *
   * @return array
   *   The rendered patch entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getRenderedPatch($patch, $view_mode) {
    $entity = $this->entityTypeManager->getStorage('patch')->load($patch);
    $view_builder = $this->entityTypeManager->getViewBuilder('patch');
    return $view_builder->view($entity, $view_mode);
  }

  /**
   * Get patch by ajax.
   *
   * @param string|int $patch
   *   The patch id requested.
   * @param string $view_mode
   *   The view mode to render the patch.
   *
   * @return AjaxResponse
   *   Return Hello string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getPatchAjax($patch, $view_mode = 'full') {

    $render_array = $this->getRenderedPatch($patch, $view_mode);

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand(
      $this->t('Change request'),
      $render_array,
      ['dialogClass' => 'popup-dialog-class', 'width' => '80%']
    ));

    return $response;
  }

}
