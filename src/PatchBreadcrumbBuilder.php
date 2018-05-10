<?php

namespace Drupal\change_requests;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PatchBreadcrumbBuilder.
 */
class PatchBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new PatchBreadcrumbBuilder object.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteName();
    $matching_routes = [
      'entity.patch.canonical',
      'entity.patch.edit_form',
      'entity.patch.apply_form',
      'entity.patch.delete_form',
    ];
    return in_array($route, $matching_routes);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {

    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route', 'url.query_args']);
    $breadcrumb->addLink(Link::createFromRoute(t('Home'), '<front>'));
    /** @var \Drupal\change_requests\Entity\Patch $patch_entity */
    $patch_entity = $route_match->getParameter('patch');

    /** @var \Drupal\node\NodeInterface $orig_entity */
    $orig_entity = $patch_entity->originalEntity();
    if ($orig_entity) {
      // Orig node link.
      $breadcrumb->addLink($orig_entity->toLink());

      // Orig node patch overview link.
      $link_patches = Link::createFromRoute(
        $this->t('Change requests'),
        'change_requests.patches_overview',
        ['node' => $orig_entity->id()]
      );
      $breadcrumb->addLink($link_patches);
    }

    return $breadcrumb;
  }

}
