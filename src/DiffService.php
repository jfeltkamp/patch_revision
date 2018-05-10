<?php

namespace Drupal\change_requests;

use DiffMatchPatch\DiffMatchPatch;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class DiffService.
 */
class DiffService {

  use StringTranslationTrait;

  /**
   * DiffMatchPatch instance.
   *
   * @var \Drupal\change_requests\Plugin\FieldPatchPluginManager
   */
  protected $dmp;

  /**
   * Get a text diff.
   *
   * @param string $str_src
   *   The old value.
   * @param string $str_target
   *   The old value.
   *
   * @return bool|string
   *   The stringified patch or FALSE if params ar no strings.
   */
  public function getTextDiff($str_src, $str_target) {
    $this->dmp = new DiffMatchPatch();
    if (is_string($str_src) && is_string($str_target)) {
      $patch = $this->dmp->patch_make($str_src, $str_target);
      $output = $this->dmp->patch_toText($patch);
      return $output;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Apply a given patch on the given value.
   *
   * @param string $value
   *   The old value to apply the patch on.
   * @param string $patch
   *   The stringified patch.
   * @param string $property
   *   The stringified patch.
   *
   * @return array
   *   The result array.
   */
  public function applyPatchText($value, $patch, $property = '') {
    $this->dmp = new DiffMatchPatch();
    try {
      $patches = $this->dmp->patch_fromText($patch);
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    if (isset($patches) && is_array($patches)) {

      $result = $this->dmp->patch_apply($patches, $value);
      $code = (count($patches))
        ? ceil((count(array_filter($result[1])) / count($result[1])) * 100)
        : 100;

      $feedback = ['code' => $code];
      if (!$code) {
        // debug: throw new ProcessFailedException($process);
        $result = $value;
        $feedback['applied'] = FALSE;
      }
      else {
        $result = $result[0];
        $feedback['applied'] = TRUE;
      }

      $result = [
        'result' => $result,
        'feedback' => $feedback,
      ];

      if ($code < 100) {
        $result['feedback']['message'] = $this->t('Could not apply patch for @property.', ['@property' => $property]);
      }
      return $result;

    }
    else {
      return [
        'result' => $value,
        'feedback' => [
          'applied' => FALSE,
          'code' => 0,
          'message' => $this->t('Could not load patch for @property.', ['@property' => $property]),
        ],
      ];
    }
  }

  /**
   * Patch formatter to display the patch in pretty HTML.
   *
   * @param string $patch
   *   The patch to apply.
   * @param string $value_old
   *   The value (!) that was $src_string when patch was created.
   *
   * @return array
   *   Render array of pretty HTML.
   */
  public function patchView($patch, $value_old) {
    $this->dmp = new DiffMatchPatch();
    try {
      $patches = $this->dmp->patch_fromText($patch);
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      $patches = [];
    }
    if (!empty($patch)) {
      $value_new = $this->dmp->patch_apply($patches, $value_old);
      $diff = $this->dmp->diff_main($value_old, $value_new[0]);
      $string = $this->dmp->diff_prettyHtml($diff);
    }
    else {
      $string = $value_old;
    }
    return [
      '#markup' => "{$string}",
    ];
  }

}
