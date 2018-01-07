<?php

/**
 * @file
 * Contains patch.page.inc.
 *
 * Page callback for Patch entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Patch templates.
 *
 * Default template: patch.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_patch(array &$variables) {
  // Fetch Patch Entity Object.
  $patch = $variables['elements']['#patch'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
