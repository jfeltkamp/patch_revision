<?php

namespace Drupal\change_requests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class AttachService.
 */
class AttachService {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new AttachService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Append a patch to a reference field of an other entity.
   *
   * @param string $to
   *   String contains address of entity, the patch is attached to. I.e. node/27/field_patches.
   * @param string $patch
   *   The id of the new patch.
   */
  public function attachPatchTo($to, $patch) {
    try {
      list($entity_type, $entity_id, $field) = explode('/', $to);
      /** @var EntityInterface $to_entity */
      $to_entity = $this->entityTypeManager->getStorage($entity_type)
        ->load($entity_id);
      $field_object = $to_entity->get($field);

      if (
        $field_object instanceof EntityReferenceFieldItemList
        && $field_object->getSetting('target_type') == 'patch'
      ) {
        $value = $field_object->getValue();
        $value[] = ['target_id' => $patch];
        $to_entity->set($field, $value);
        $to_entity->save();
      }
    } catch (\Exception $exception) {
      $message = t('Could not attach patch with id "%id" to "%to". Read further information in log messages.', [
        '%id' => $patch,
        '%to' => $to,
      ]);
      \Drupal::messenger()->addError($message);
      \Drupal::logger('change_requests')->error($exception->getMessage());
    }
  }

}
