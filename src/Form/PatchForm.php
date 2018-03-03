<?php

namespace Drupal\patch_revision\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\patch_revision\Events\PatchRevision;

/**
 * Form controller for Patch edit forms.
 *
 * @ingroup patch_revision
 */
class PatchForm extends ContentEntityForm {

  /**
   * @var PatchRevision
   */
  protected $constants;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->constants = new PatchRevision();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\patch_revision\Entity\Patch */
    $form = parent::buildForm($form, $form_state);

    if (\Drupal::currentUser()->hasPermission('change status of patch entities')) {
      $form['status'] = [
        '#type' => 'select',
        '#title' => $this->t('Status'),
        '#description' => $this->t('Status of the improvement. Set to "active" if improvement shall be applied to original entity.', [
          '@status' => $this->constants->getStatusLiteral(1)
        ]),
        '#options' => PatchRevision::PR_STATUS,
        '#default_value' => $this->entity->get('status')->getString(),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    $entity->set('status', $form_state->getValue('status'));

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label improvement.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label improvement.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.patch.canonical', ['patch' => $entity->id()]);
  }

}
