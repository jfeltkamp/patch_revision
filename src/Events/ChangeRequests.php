<?php

namespace Drupal\change_requests\Events;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Contains global constants for status management.
 */
final class ChangeRequests {
  use StringTranslationTrait;

  /* @var integer
   *   Stored value for pro argument.
   */
  const CR_STATUS_ACTIVE = 1;

  /* @var string
   *   Stored value for pro argument.
   */
  const CR_STATUS_ACTIVE_TXT = 'proposed';

  /* @var integer
   *   Stored value for con argument.
   */
  const CR_STATUS_CONFLICTED = 2;
  const CR_STATUS_CONFLICTED_TXT = 'conflicted';

  /* @var integer
   *   Stored value for con argument.
   */
  const CR_STATUS_PATCHED = 3;
  const CR_STATUS_PATCHED_TXT = 'applied';

  /* @var integer
   *   Stored value for con argument.
   */
  const CR_STATUS_DECLINED = 4;
  const CR_STATUS_DECLINED_TXT = 'declined';

  /* @var integer
   *   Stored value for con argument.
   */
  const CR_STATUS_DISABLED = 0;
  const CR_STATUS_DISABLED_TXT = 'disabled';

  /* @var integer
   *   The default value for argument type.
   */
  const CR_STATUS_DEFAULT = self::CR_STATUS_ACTIVE;

  const CR_STATUS = [
    self::CR_STATUS_ACTIVE => self::CR_STATUS_ACTIVE_TXT,
    self::CR_STATUS_CONFLICTED => self::CR_STATUS_CONFLICTED_TXT,
    self::CR_STATUS_PATCHED => self::CR_STATUS_PATCHED_TXT,
    self::CR_STATUS_DECLINED => self::CR_STATUS_DECLINED_TXT,
    self::CR_STATUS_DISABLED => self::CR_STATUS_DISABLED_TXT,
  ];


  const CODE_PATCH_EMPTY = 1001;

  /**
   * Returns a literal from id.
   *
   * @param int $status
   *   The status id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A translatable string for status.
   */
  public function getStatusLiteral($status) {
    switch ((int) $status) {
      case 0:
        $label = $this->t('disabled');
        break;

      case 1:
        $label = $this->t('active');;
        break;

      case 2:
        $label = $this->t('conflicted');
        break;

      case 3:
        $label = $this->t('patched');
        break;

      case 4:
        $label = $this->t('declined');
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
    return self::CR_STATUS[$id];
  }

}
