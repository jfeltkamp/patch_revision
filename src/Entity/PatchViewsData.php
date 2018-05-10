<?php

namespace Drupal\change_requests\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Patch entities.
 */
class PatchViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
