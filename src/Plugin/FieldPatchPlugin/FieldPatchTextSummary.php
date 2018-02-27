<?php
/**
 * Created by:
 * User: jfeltkamp
 * Date: 09.03.16
 * Time: 22:24
 */

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\patch_revision\Annotation\FieldPatchPlugin;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "text_with_summary",
 *   label = @Translation("FieldPatchPlugin for field type Texts with summary"),
 *   field_types = {
 *     "text_with_summary",
 *   },
 *   properties = {
 *     "summary" = "",
 *     "value" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchTextSummary extends FieldPatchDiffable {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'text_with_summary';
  }

}