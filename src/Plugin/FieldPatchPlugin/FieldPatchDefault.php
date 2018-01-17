<?php
/**
 * Created by:
 * User: jfeltkamp
 * Date: 09.03.16
 * Time: 22:24
 */

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\patch_revision\Plugin\FieldPatchPluginBase;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
   * @param $str_src mixed
   * @param $str_target mixed
   *
   * @return mixed
   */
  public function processValueDiff($str_src, $str_target) {

    $process_str = "git diff $(echo \"{$str_src}\" | git hash-object -w --stdin) $(echo \"{$str_target}\" | git hash-object -w --stdin)  --word-diff --abbrev=4";

    $process = new Process($process_str);
    $process->run();

    // executes after the command finishes
    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    return $process->getOutput();
  }

  /**
   * {@inheritdoc}
   */
  public function patchField() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function patchStringFormatter($string) {
    $string = $this->cutDiffHead($string);
    return [
      '#markup' => "<pre>{$string}</pre>"
    ];
  }

  /**
   * Get rid of the git diff header.
   *
   * @param $string
   *   Original string.
   * @return $string
   *   Cleaned string.
   */
  protected function cutDiffHead($string) {
    preg_match('/@@[0-9,\-+ ]+@@\s([\w\W]*)$/', $string, $result);
    return count($result) ? (string) $result[1] : $string;
  }

}