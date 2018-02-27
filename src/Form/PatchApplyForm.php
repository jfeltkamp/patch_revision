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
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\patch_revision\DiffService;
use Drupal\patch_revision\Events\PatchRevision;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   * @var PatchRevision
   */
  protected $constants;

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
    $this->constants = new PatchRevision();
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
    // CHECK STATUS
    if (($status = (int) $form_state->getValue('status')) !== PatchRevision::PR_STATUS_ACTIVE) {
      if (\Drupal::currentUser()->hasPermission('change status of patch entities')) {
        $this->entity->set('status', $status);
        $this->entity->save();
      }
      drupal_set_message(
        $this->t('Status was set to "@status". The status must be "@active" to apply improvements.', [
          '@status' => $this->constants->getStatusLiteral($status),
          '@active' => $this->constants->getStatusLiteral(1),
        ]), 'warning');

      $form_state->setRedirectUrl($this->entity->toUrl());
      return;
    }

    // CHECK ORIGINAL ENTITY
    if (!$orig_entity = $this->entity->originalEntityRevision('latest')) {
      drupal_set_message($this->t('Original entity could not be loaded. Seems as Improvement is obsolet.'), 'error');
      return;
    }

    // APPLY SUCCEEDED
    /** @var \Drupal\field\Entity\FieldConfig[] $field_defs */
    $field_defs = $orig_entity->getFieldDefinitions();
    foreach ($this->entity->getPatchField() as $name => $patch) {
      if(isset($field_defs[$name])) {
        // We must filter values because smt. $form_state->getValue() returns widget elements as btn "Add more items".
        $field_plugin = $this->entity->getPluginManager()->getPluginFromFieldType($field_defs[$name]->getType());
        $form_value = ($field_plugin)
          ? $field_plugin->prepareData($form_state->getValue($name))
          : $form_state->getValue($name);
        $new_value = array_filter($form_value, function($val, $key) use($field_plugin) {
          return $field_plugin->validateDataIntegrity($val);
        },ARRAY_FILTER_USE_BOTH);

        $orig_entity->set($name, $new_value);
      } else {
        drupal_set_message($this->t('Field "@field" is not defined and can not be patched.', [
          '@field' => $name,
        ]), 'warning');
      }
    }
    // Set revision information.
    $orig_entity->setNewRevision(TRUE);
    /** @var UserInterface|FALSE $patch_creator */
    $users = $this->entity->get('uid')->referencedEntities();
    $patch_creator = reset($users);
    $message = $this->t('Applied improvement with id "@id" of user "@user" with message "@message".', [
      '@id' => $this->entity->id(),
      '@user' => ($patch_creator) ? $patch_creator->getAccountName() : $this->t('Anonymous'),
      '@message' => $this->entity->get('message')->getString(),
    ]);
    $orig_entity->set('revision_log', $message);
    $orig_entity->save();

    $this->entity->set('status', PatchRevision::PR_STATUS_PATCHED);
    $this->entity->save();
    drupal_set_message($message);

    $form_state->setRedirectUrl($orig_entity->toUrl());
    return;
  }

  /**
   * Defines the settings form for Patch entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|RedirectResponse
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if (($status = (int) $this->entity->get('status')->getString()) !== PatchRevision::PR_STATUS_ACTIVE) {
      drupal_set_message(
        $this->t('Status is "@status". The status must be "@active" to apply the improvement.', [
          '@status' => $this->constants->getStatusLiteral($status),
          '@active' => $this->constants->getStatusLiteral(1),
        ]), 'warning');

      /** @var \Drupal\Core\Url $url */
      $url = $this->entity->toUrl();
      return new RedirectResponse($url->toString());
    }

    $form['#parents'] = [];
    $form['#attached']['library'][] = 'patch_revision/patch_revision.apply_form';

    /** @var NodeInterface $orig_entity */
    $orig_entity = $this->entity->originalEntityRevision('latest');
    /** @var NodeInterface $orig_entity_old */
    $orig_entity_old = $this->entity->originalEntityRevisionOld();

    $patch = $this->entity->getPatchField();
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
      $config = ($field_type == 'entity_reference')
        ? ['entity_type' => $field_old->getSetting('target_type')]
        : [];
      $field_patch_plugin = $this->entity->getPluginManager()->getPluginFromFieldType($field_type, $config);
      if ($field_patch_plugin) {
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
      } else {
        drupal_set_message($this->t('FieldPatch plugin missing for field_type @field_type', [
            '@field_type' => $field_type
          ])
        );
      }

    }

    if (\Drupal::currentUser()->hasPermission('change status of patch entities')) {
      $form['status'] = [
        '#type' => 'select',
        '#title' => $this->t('Status'),
        '#description' => $this->t('Status of the improvement. Set to "active" if improvement shall be applied to original entity.', [
          '@status' => $this->constants->getStatusLiteral(1)
        ]),
        '#options' => PatchRevision::PR_STATUS,
        '#default_value' => $this->entity->get('status')->getString(),
      ];
    }

    $form += parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * Returns the action form element for the current entity form.
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    unset($element['delete']);
    if (isset($element['submit'])) {
      $element['submit']['#value'] = new TranslatableMarkup('Apply improvement');
    }
    return $element;
  }

}
