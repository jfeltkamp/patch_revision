<?php

namespace Drupal\patch_revision;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AccessService.
 */
class AccessService {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $moduleConfig;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Symfony\Component\HttpFoundation\Request definition.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new AccessService object.
   */
  public function __construct(ConfigFactory $config_factory, AccountProxy $current_user, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->moduleConfig = $this->configFactory->get('patch_revision.config');
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->currentRequest = $this->requestStack->getCurrentRequest();
  }

  /**
   * Check finally if checkbox "Create patch from changes" to be displayed.
   *
   * @param string $node_type
   *   The node bundle to check for, if it is configured.
   *
   * @return bool
   *   The result.
   */
  public function displayCheckboxCreatePatch($node_type) {
    // Check user has permission.
    if (!$this->currentUser->hasPermission('add patch entities')) {
      return FALSE;
    }

    // Check if "Display create-patch-checkbox on node forms" is enabled.
    if(!$this->moduleConfig->get('enable_checkbox_node_form')) {
      return FALSE;
    }

    // Check if node type is enabled for patch_revision.
    if(!in_array($node_type, $this->moduleConfig->get('node_types'))) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check finally if log message shall be a required value.
   *
   * @return bool
   *   Restult.
   */
  public function logMessageRequired() {
    if (!$this->moduleConfig->get('log_message_required')) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   *
   */
  public function startPatchCreateProcess() {
    if (!$this->currentRequest->get('_route') == 'entity.node.edit_form') {
      return FALSE;
    }

    if ($this->currentRequest->get('create_patch') !== "1") {
      return FALSE;
    }

    if (!$this->currentUser->hasPermission('add patch entities')) {
      return FALSE;
    }

    return TRUE;
  }

}
