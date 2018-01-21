<?php
/**
 * Created by PhpStorm.
 * User: jfeltkamp
 * Date: 25.09.17
 * Time: 16:15
 */
namespace Drupal\patch_revision\Events;

final class PatchRevision {

  /**
   * @var integer
   *   Stored value for pro argument.
   */
  const PR_STATUS_ACTIVE = 1;

  /**
   * @var integer
   *   Stored value for con argument.
   */
  const PR_STATUS_CONFLICTED = 2;

  /**
   * @var integer
   *   Stored value for con argument.
   */
  const PR_STATUS_PATCHED = 3;

  /**
   * @var integer
   *   Stored value for con argument.
   */
  const PR_STATUS_DISABLED = 0;

  /**
   * @var integer
   *   The default value for argument type.
   */
  const PR_STATUS_DEFAULT = self::PR_STATUS_ACTIVE;

}