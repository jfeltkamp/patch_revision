<?php

namespace Drupal\change_requests;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Patch entity.
 *
 * @see \Drupal\change_requests\Entity\Patch.
 */
class PatchAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\change_requests\Entity\Patch $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view patch entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit patch entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete patch entities');

      case 'apply':
        return AccessResult::allowedIfHasPermission($account, 'apply patch entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add patch entities');
  }

}
