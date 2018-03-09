<?php

namespace Drupal\patch_revision\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Patch entities.
 *
 * @ingroup patch_revision
 */
class PatchDeleteForm extends ContentEntityDeleteForm {


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $nid = $entity->get('rid')->getString();
    $redirect = Url::fromRoute('patch_revision.patches_overview', ['node' => $nid]);

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

  public function buildForm(array $form, FormStateInterface $form_state) {

    $header_data = $this->getEntity()->getViewHeaderData();
    $form['#title'] = $this->t('Delete improvement for @type: @title', [
      '@type' => $header_data['orig_type'],
      '@title' => $header_data['orig_title'],
    ]);

    $form['header'] = [
      '#theme' => 'pr_patch_header',
      '#created' => $header_data['created'],
      '#creator' => $header_data['creator'],
      '#log_message' => $header_data['log_message'],
      '#attached' => [
        'library' => ['patch_revision/patch_revision.pr_patch_header'],
      ]
    ];

    $form += parent::buildForm($form, $form_state);
    return $form;
  }
}
