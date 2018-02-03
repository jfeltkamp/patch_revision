<?php

namespace Drupal\patch_revision\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\patch_revision\Plugin\FieldPatchPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PatchSettingsForm.
 *
 * @ingroup patch_revision
 */
class PatchApplyForm extends ContentEntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\patch_revision\Entity\Patch
   */
  protected $entity;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * FieldPatchPluginManager.
   *
   * @var \Drupal\patch_revision\Plugin\FieldPatchPluginManager
   */
  protected $patchPluginManager;

  /**
   * @var FormBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    EntityManagerInterface $entity_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    TimeInterface $time = NULL,
    EntityFieldManager $entity_field_manager,
    EntityTypeManager $entity_type_manager,
    FieldPatchPluginManager $patch_plugin_manager,
    FormBuilder $form_builder
  ) {
    parent::__construct( $entity_manager, $entity_type_bundle_info,$time);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->patchPluginManager = $patch_plugin_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field_patch_plugin'),
      $container->get('form_builder')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'patch.apply_form';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * Defines the settings form for Patch entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#parents'] = [];
    $form['#attached']['library'][] = 'patch_revision/patch_revision.apply_form';

    /** @var NodeInterface $orig_entity */
    $orig_entity = $this->entity->originalEntity(TRUE);

    $form_id = [
      $orig_entity->getEntityTypeId(),
      $orig_entity->bundle(),
      'default'
    ];
    $form_id = implode('.', $form_id);

    /** @var EntityFormDisplay $entity_form_display */
    $entity_form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($form_id);

    $patches = $this->entity->get('patch')->getValue();
    $patch = count($patches) ? $patches[0] : [];
    foreach ($patch as $field_name => $value) {
      $field_patch_plugin = $this->entity->getPatchPluginFromOrigFieldName('node', $field_name);
      $field_label = $this->entity->getOrigFieldLabel($field_name);

      $form[$field_name.'_group'] = [
        '#type' => 'fieldset',
        '#legend' => $field_label,
        '#open' => TRUE,
        '#attributes' => ['class' => [
          'patch_revision_apply_group',
          'patch_revision_apply_' . $field_name,
        ]],
        'left' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['group_left']]
        ],
        'right' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['group_right']]
        ],
      ];
      $form[$field_name.'_group']['left'][$field_name.'_patch'] =  $field_patch_plugin->getFieldPatchView($field_label, $value);
      $form[$field_name.'_group']['right'][$field_name] = $this->getOrigFieldWidget($form, $form_state, $entity_form_display, $field_name, $orig_entity);
    }

    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @param $entity_form_display
   * @param $field_name
   * @param $orig_entity
   * @return array
   */
  protected function getOrigFieldWidget(array $form, FormStateInterface $form_state, EntityFormDisplay $entity_form_display, $field_name, NodeInterface $orig_entity) {
    if ($widget = $entity_form_display->getRenderer($field_name)) {
      $items = $orig_entity->get($field_name);
      $items->filterEmptyItems();
      $field = $widget->form($items, $form, $form_state);
      $field['#access'] = $items->access('edit');
      return $field;
    }
    return [];
  }


}
