<?php
/**
 * Created by:
 * User: jfeltkamp
 * Date: 09.03.16
 * Time: 22:24
 */

namespace Drupal\patch_revision\Plugin\FieldPatchPlugin;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\patch_revision\Annotation\FieldPatchPlugin;
use Drupal\Core\Annotation\Translation;
use Drupal\patch_revision\Plugin\FieldPatchPluginBase;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "daterange",
 *   label = @Translation("FieldPatchPlugin for field type daterange."),
 *   field_types = {
 *     "daterange",
 *   },
 *   properties = {
 *     "value" = "",
 *     "end_value" = "",
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchDaterange extends FieldPatchDateTime {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'daterange';
  }

}