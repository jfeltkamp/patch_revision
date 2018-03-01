<?php
/**
 * Created by PhpStorm.
 * User: jfeltkamp
 * Date: 25.09.17
 * Time: 16:15
 */
namespace Drupal\patch_revision\Events;


use Drupal\Core\StringTranslation\StringTranslationTrait;

final class PatchRevision {
  use StringTranslationTrait;

  /**
   * @var integer
   *   Stored value for pro argument.
   */
  const PR_STATUS_ACTIVE = 1;
  const PR_STATUS_ACTIVE_TXT = 'proposed';

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
  const PR_STATUS_PATCHED_TXT = 'applied';

  /**
   * @var integer
   *   Stored value for con argument.
   */
  const PR_STATUS_DECLINED = 4;
  const PR_STATUS_DECLINED_TXT = 'declined';

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
    self::PR_STATUS_DECLINED => self::PR_STATUS_DECLINED_TXT,
    self::PR_STATUS_DISABLED => self::PR_STATUS_DISABLED_TXT,
  ];


  const CODE_PATCH_EMPTY = 1001;

  /**
   * @param int $status
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  public function getStatusLiteral($status) {
    switch ((int) $status) {
      case 0:
        $label = $this->t(self::PR_STATUS_DISABLED_TXT);
        break;
      case 1:
        $label = $this->t(self::PR_STATUS_ACTIVE_TXT);
        break;
      case 2:
        $label = $this->t(self::PR_STATUS_CONFLICTED_TXT);
        break;
      case 3:
        $label =  $this->t(self::PR_STATUS_PATCHED_TXT);
        break;
      case 4:
        $label =  $this->t(self::PR_STATUS_DECLINED_TXT);
        break;
      default:
        return $this->t('undefined');
    }
    return $label;
  }

  /**
   * Returns machine readable string for status.
   *
   * @param int $id
   *   The integer status ID.
   *
   * @return string
   *   The string status ID.
   */
  public function getStatus($id) {
    return self::PR_STATUS[$id];
  }
}