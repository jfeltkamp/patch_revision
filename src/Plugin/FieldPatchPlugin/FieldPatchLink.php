<?php

namespace Drupal\change_requests\Plugin\FieldPatchPlugin;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @FieldPatchPlugin(
 *   id = "link",
 *   label = @Translation("FieldPatchPlugin for all field types of link"),
 *   fieldTypes = {
 *     "link",
 *   },
 *   properties = {
 *     "uri" = {
 *       "label" = @Translation("Url"),
 *       "default_value" = "",
 *       "patch_type" = "full",
 *     },
 *     "title" = {
 *       "label" = @Translation("Link text"),
 *       "default_value" = "",
 *       "patch_type" = "diff",
 *     },
 *   },
 *   permission = "administer nodes",
 * )
 */
class FieldPatchLink extends FieldPatchData {

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'link';
  }

  /**
   * {@inheritdoc}
   */
  public function getDiffTitle($str_src, $str_target) {
    return $this->diff->getTextDiff($str_src, $str_target);
  }

  /**
   * {@inheritdoc}
   */
  public function applyPatchTitle($value, $patch) {
    return $this->diff->applyPatchText($value, $patch, $this->t('link text'));
  }

  /**
   * {@inheritdoc}
   */
  public function patchFormatterTitle($patch, $value_old) {
    return $this->diff->patchView($patch, $value_old);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDataIntegrity($value) {
    $properties = ['uri' => "", 'title' => ""];
    return count(array_intersect_key($properties, $value)) == count($properties);
  }

}
