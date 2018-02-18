<?php
/**
 * Created by:
 * User: jfeltkamp
 * Date: 09.03.16
 * Time: 22:24
 */

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\patch_revision\Events\PatchRevision;
use Drupal\patch_revision\Plugin\FieldPatchPluginBase;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drupal\patch_revision\Annotation\FieldPatchPlugin;
use Drupal\Core\Annotation\Translation;
use DiffMatchPatch\DiffMatchPatch;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "default",
 *   label = @Translation("Set promoted/Unset promoted"),
 *   description = @Translation("Set/unset promote property of the parent node of this field."),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *   },
 *   properties = {
 *     "value" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchDefault extends FieldPatchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'default';
  }

  /**
   * {@inheritdoc}

      $process_str = "git diff $(echo \"{$str_src}\" | git hash-object -w --stdin --path=foobar.txt) $(echo \"{$str_target}\" | git hash-object -w --stdin --path=foobar.txt)  --word-diff --abbrev=4";

      $process = new Process($process_str);
      $process->run();

      // executes after the command finishes
      if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
      }
      $output = $process->getOutput();
      $output = preg_replace('/^[^@]+/', '', $output);
   */
  public function processValueDiff($str_src, $str_target) {

    if (is_string($str_src) && is_string($str_target)) {
      $dmp = new DiffMatchPatch();
      $patch = $dmp->patch_make($str_src, $str_target);
      $output = $dmp->patch_toText($patch);
      return $output;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processPatchFieldValue($value, $patch) {
    $dmp = new DiffMatchPatch();
    try {
      $patches = $dmp->patch_fromText($patch);
    } catch(\Exception $e) {
      drupal_set_message($e->getMessage()) ;
    }

    if (isset($patches) && is_array($patches)) {

      $result = $dmp->patch_apply($patches, $value);
      $code = (count($patches))
        ? ceil((count(array_filter($result[1]))/count($result[1])) * 100)
        : 100;

      $feedback = ['code' => $code];
      if (!$code) {
        // debug: throw new ProcessFailedException($process);
        $result = $value;
        $feedback['applied'] = FALSE;
      } else {
        $result = $result[0];
        $feedback['applied'] = TRUE;
      }

      return [
        'result' => $result,
        'feedback' => $feedback,
      ];

    } else {
      return [
        'result' => $value,
        'feedback' => [
          'applied' => FALSE,
          'code' => 0,
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function patchStringFormatter($patch, $value_old) {
    $dmp = new DiffMatchPatch();
    $patches = $dmp->patch_fromText($patch);
    $value_new = $dmp->patch_apply($patches, $value_old);
    $diff = $dmp->diff_main($value_old, $value_new[0]);
    $string = $dmp->diff_prettyHtml($diff);
    return [
      '#markup' => "{$string}"
    ];
  }

}