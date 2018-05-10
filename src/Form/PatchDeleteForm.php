<?php

namespace Drupal\change_requests\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Patch entities.
 *
 * @ingroup change_requests
 */
class PatchDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $nid = $entity->get('rid')->getString();
    $redirect = Url::fromRoute('change_requests.patches_overview', ['node' => $nid]);

    // Make sure that deleting a translation does not delete the whole entity.
    if (!$entity->isDefaultTranslation()) {
      $untranslated_entity = $entity->getUntranslated();
      $untranslated_entity->removeTranslation($entity->language()->getId());
      $untranslated_entity->save();
      $form_state->setRedirectUrl($untranslated_entity->urlInfo('canonical'));
    }
    else {
      $entity->delete();
      $form_state->setRedirectUrl($redirect);
    }

    drupal_set_message($this->getDeletionMessage());
    $this->logDeletionMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header_data = $this->getEntity()->getViewHeaderData();
    $form['#title'] = $this->t('Delete change request for @type: @title', [
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

    $form += parent::buildForm($form, $form_state);
    return $form;
  }

}
