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
   */
  public function processValueDiff($str_src, $str_target) {

    if (is_string($str_src) && is_string($str_target)) {
      $process_str = "git diff $(echo \"{$str_src}\" | git hash-object -w --stdin --path=foobar.txt) $(echo \"{$str_target}\" | git hash-object -w --stdin --path=foobar.txt)  --word-diff --abbrev=4";

      $process = new Process($process_str);
      $process->run();

      // executes after the command finishes
      if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
      }
      $output = $process->getOutput();
      $output = preg_replace('/^[^@]+/', '', $output);

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
    if (FALSE && !empty($patch)) {
      // DISABLED BY FALSE CONDITION
      /* @ToDo
       * The patching of word-diff does not work at all.
       * i.e. "... Lorem ipsum dolor[- sit,-]{+ amet,+} consectetuer adipiscing elit. ..."
       * So the next ideas are,
       * * to find a php library as that is able to do this.
       * * * nuxodin/diff_match_patch-php (check if it works fine)
       *
       * * to write an own library, that is able to patch it.
       */

      // $value = str_replace(array("\r\n", "\r", "\n"),"\n", $value);
      $valueFileHandle = $this->createNamTempFile(PatchRevision::PR_PATCH_TEMP_FILE_NAME, (string) $value);
      $valueFileMetaData = stream_get_meta_data($valueFileHandle);

      $path_frags = explode('/', $valueFileMetaData['uri']);
      $file_name = array_pop($path_frags);
      $file_path = implode('/', $path_frags);


      $file_header = preg_replace('/FILENAME/', $file_name, "diff --git a/FILENAME b/FILENAME\n"
        . "--- a/FILENAME \n"
        . "+++ b/FILENAME \n");

      $patch_file_content = str_replace(array("\r\n", "\r", "\n"),"\n", $file_header.$patch."\n");

      $patchFileHandle = $this->createNamTempFile(PatchRevision::PR_ORIG_TEMP_FILE_NAME, $patch_file_content);
      $patchFileMetaData = stream_get_meta_data($patchFileHandle);


      $process = new Process(sprintf(
        'cd %s && git apply --unsafe-paths --ignore-whitespace --ignore-space-change --whitespace=fix %s',
        escapeshellarg($file_path),
        escapeshellarg($patchFileMetaData['uri'])
      ));
      $code = $process->run();

      $feedback = ['code' => $code];
      if (!$process->isSuccessful()) {
        // debug: throw new ProcessFailedException($process);
        $result = $value;
        $feedback['applied'] = FALSE;
      } else {
        $result = file_get_contents($valueFileMetaData['uri']);
        $feedback['applied'] = TRUE;
      }

      unlink($valueFileHandle);
      unlink($patchFileHandle);

      return [
        'result' => $result,
        'feedback' => $feedback,
      ];

    } else {
      return [
        'result' => $value,
        'feedback' => [
          'applied' => FALSE,
          'code' => PatchRevision::CODE_PATCH_EMPTY,
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function patchStringFormatter($string) {
    $string = $this->cutDiffHead($string);
    $string = $this->insertInsDelFormatter($string);
    return [
      '#markup' => "{$string}"
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function insertInsDelFormatter($string) {
    $string = preg_replace('/\[-([\s\S]*?)-\]/', '<del class="diffdel"> ${1}</del>', $string);
    $string = preg_replace('/{\+([\s\S]*?)\+}/', '<ins class="diffins"> ${1}</ins>', $string);
    return nl2br($string);
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