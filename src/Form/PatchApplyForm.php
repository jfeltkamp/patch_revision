<?php

namespace Drupal\patch_revision\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Exception\ReadOnlyException;
use Drupal\node\NodeInterface;
use Drupal\patch_revision\DiffService;
use Drupal\patch_revision\Events\PatchRevision;
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
   * DiffService.
   *
   * @var \Drupal\patch_revision\DiffService
   */
  protected $diffService;

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
    DiffService $diff_service,
    FormBuilder $form_builder
  ) {
    parent::__construct( $entity_manager, $entity_type_bundle_info,$time);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->diffService = $diff_service;
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
      $container->get('patch_revision.diff'),
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
    // $patch = $this->entity->patch();
    // $orig_entity = $this->entity->originalEntityRevision('latest');


    $form = $form;
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
    $orig_entity = $this->entity->originalEntityRevision('latest');
    /** @var NodeInterface $orig_entity_old */
    $orig_entity_old = $this->entity->originalEntityRevisionOld();

    $patch = $this->entity->patch();
    foreach ($patch as $field_name => $field_patch) {

      // Build frame for each field.
      $field_label = $this->entity->getOrigFieldLabel($field_name);
      $form[$field_name.'_group'] = [
        '#type' => 'fieldset',
        '#title' => $field_label,
        '#open' => TRUE,
        '#attributes' => ['class' => [
          'pr_apply_group',
          'pr_apply_' . $field_name,
        ]],
        'left' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['group_left']],
          'header' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['header_left']],
            'content' => ['#markup' => $this->t('Changes')],
          ],
        ],
        'right' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['group_right']],
          'header' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['header_right']],
            'content' => ['#markup' => $this->t('Current')],
          ],
        ],
      ];

      // Left side content. Old Value with highlighted patch
      $field_old = $orig_entity_old->get($field_name);
      $field_type = $this->entity->getEntityFieldType($field_name);
      $field_patch_plugin = $this->entity->getPluginManager()->getPluginFromFieldType($field_type);
      $result_old = $field_patch_plugin->getFieldPatchView($field_patch, $field_old);
      $form[$field_name.'_group']['left'][$field_name.'_patch'] = $result_old;

      // Right side. Latest value form element with patch applied.
      $form_id = implode('.', [$orig_entity->getEntityTypeId(), $orig_entity->bundle(), 'default']);
      /** @var EntityFormDisplay $entity_form_display */
      $entity_form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($form_id);
      $widget = $entity_form_display->getRenderer($field_name);
      $value_latest = $orig_entity->get($field_name);
      $patched_value = $field_patch_plugin->patchFieldValue($value_latest->getValue(), $field_patch);
      try {
        $value_latest->setValue($patched_value['result']);
      } catch (\Exception $e) {
        if ($e instanceof \InvalidArgumentException) {}
        if ($e instanceof ReadOnlyException) {}
      }
      $orig_field_widget = $widget->form($value_latest, $form, $form_state);
      $field_patch_plugin->setWidgetFeedback($orig_field_widget, $patched_value['feedback']);
      $orig_field_widget['#access'] = $value_latest->access('edit');
      $form[$field_name.'_group']['right'][$field_name] = $orig_field_widget;

    }


    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#description' => $this->t('Status of the patch revision.'),
      '#options' => PatchRevision::PR_STATUS,
      '#default_value' => $this->entity->get('status')->getString(),
    ];

    $form += parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * Returns the action form element for the current entity form.
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    // unset($element['delete']);
    if (isset($element['submit'])) {
      $element['submit']['#value'] = new TranslatableMarkup('Save improvement to document');
    }
    return $element;
  }

}
