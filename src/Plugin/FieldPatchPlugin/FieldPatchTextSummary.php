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
 *   id = "text_with_summary",
 *   label = @Translation("Texts with summary"),
 *   description = @Translation("Diff plugin for all texts with summary."),
 *   field_types = {
 *     "text_with_summary",
 *   },
 *   properties = {
 *     "value" = "",
 *     "summary" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchTextSummary extends FieldPatchDefault {
}