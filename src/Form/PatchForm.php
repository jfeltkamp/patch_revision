<?php

namespace Drupal\change_requests\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\change_requests\Events\ChangeRequests;

/**
 * Form controller for Patch edit forms.
 *
 * @ingroup change_requests
 */
class PatchForm extends ContentEntityForm {

  /**
   * The change_requests environment variables.
   *
   * @var \Drupal\change_requests\Events\ChangeRequests
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
    $this->constants = new ChangeRequests();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\change_requests\Entity\Patch */
    $form = parent::buildForm($form, $form_state);

    $header_data = $this->entity->getViewHeaderData();
    $form['#title'] = $this->t('Edit change request for @type: @title', [
      '@type' => $header_data['orig_type'],
      '@title' => $header_data['orig_title'],
    ]);

    $form['header'] = [
      '#theme' => 'cr_patch_header',
      '#created' => $header_data['created'],
      '#creator' => $header_data['creator'],
      '#log_message' => $header_data['log_message'],
      '#attached' => [
        'library' => ['change_requests/cr_patch_header'],
      ],
    ];

    if (\Drupal::currentUser()->hasPermission('change status of patch entities')) {
      $form['status'] = [
        '#type' => 'select',
        '#title' => $this->t('Status'),
        '#description' => $this->t('Status of the change request. Set to "active" if change request shall be applied to original entity.', [
          '@status' => $this->constants->getStatusLiteral(1),
        ]),
        '#options' => ChangeRequests::CR_STATUS,
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
        drupal_set_message($this->t('Created the %label change request.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label change request.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.patch.canonical', ['patch' => $entity->id()]);
  }

}
