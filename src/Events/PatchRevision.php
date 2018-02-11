<?php
/**
 * Created by PhpStorm.
 * User: jfeltkamp
 * Date: 25.09.17
 * Time: 16:15
 */
namespace Drupal\patch_revision\Events;

final class PatchRevision {


  const PR_PATCH_TEMP_FILE_NAME = 'FOO';
  const PR_ORIG_TEMP_FILE_NAME = 'PAT';

  /**
   * @var integer
   *   Stored value for pro argument.
   */
  const PR_STATUS_ACTIVE = 1;
  const PR_STATUS_ACTIVE_TXT = 'active';

  /**
   * @var integer
   *   Stored value for con argument.
   */
  const PR_STATUS_CONFLICTED = 2;
  const PR_STATUS_CONFLICTED_TXT = 'conflicted';

  /**
   * @var integer
   *   Stored value for con argument.
   */
  const PR_STATUS_PATCHED = 3;
  const PR_STATUS_PATCHED_TXT = 'patched';

  /**
   * @var integer
   *   Stored value for con argument.
   */
  const PR_STATUS_DISABLED = 0;
  const PR_STATUS_DISABLED_TXT = 'disabled';

  /**
   * @var integer
   *   The default value for argument type.
   */
  const PR_STATUS_DEFAULT = self::PR_STATUS_ACTIVE;

  const PR_STATUS = [
    self::PR_STATUS_ACTIVE => self::PR_STATUS_ACTIVE_TXT,
    self::PR_STATUS_CONFLICTED => self::PR_STATUS_CONFLICTED_TXT,
    self::PR_STATUS_PATCHED => self::PR_STATUS_PATCHED_TXT,
    self::PR_STATUS_DISABLED => self::PR_STATUS_DISABLED_TXT,
  ];


  const CODE_PATCH_EMPTY = 1001;
}