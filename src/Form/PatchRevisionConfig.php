<?php

namespace Drupal\patch_revision\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class PatchRevisionConfig.
 */
class PatchRevisionConfig extends ConfigFormBase {

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
   * Constructs a new PatchRevisionConfig object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityTypeManager $entity_type_manager,
      EntityFieldManager $entity_field_manager
    ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'patch_revision.config',
    ];
  }

  /**
   *
   */
  protected function getPatchableFields($node_type) {
    $fields = $this->entityFieldManager->getFieldDefinitions('node', $node_type);
    foreach ($fields as $name => $field) {
      /* @var $field \Drupal\Core\Field\FieldDefinitionInterface */
      $type = $field->getType();
      if (!in_array($type, ['string', 'text_with_summary'])) {
        unset($fields[$name]);
      }
    }
    return $fields;
  }


  /**
   * @var $patchable_fields \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected function getFieldOptions($patchable_fields) {
    $options = [];
    foreach ($patchable_fields as $name => $field_definition) {
      /** @var $field_definition \Drupal\Core\Field\FieldDefinitionInterface */
      $options[$name] = $field_definition->getLabel();
    }
    return $options;
  }



  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'patch_revision_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('patch_revision.config');
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $options = [];
    foreach ($node_types as $name => $node_type) {
      $options[$name] = $node_type->label();
    }

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node Types'),
      '#description' => $this->t('Check node types where to provide a patch functionality.'),
      '#options' => $options,
      '#default_value' => $config->get('node_types'),
      '#ajax' => [
        'callback' => [$this, 'bundleSelected'],
        'wrapper' => 'bundle_select-wrapper',
      ],
    ];
    $form['bundle_select'] = $this->getAjaxWrapperElement('bundle_select');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('patch_revision.config')
      ->set('node_types', $form_state->getValue('node_types'))
      ->save();
  }

  /**
   * Returns additional form elements after selecting the desired bundle.
   *
   * @param array $form
   *   The form in it's current state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface holding the values.
   *
   * @return array
   *   The form elements to insert into the form.
   */
  public function bundleSelected(array &$form, FormStateInterface $form_state) {

    $bundles = $form_state->getCompleteForm()['node_types']['#value'];

    // Prepare the form element to insert (an empty placeholder).
    $element['bundle_select'] = $this->getAjaxWrapperElement('bundle_select');

    // Only if an entity type is selected, we are
    // able to provide further elements.

    foreach ($bundles as $key => $bundle) {
      $options = $this->getFieldOptions($this->getPatchableFields($key));

      // Prepare a container to hold the additional form elements.
      $element['bundle_select']['bundle_' . $key . '_fields'] = [
        '#type' => 'select',
        '#title' => $this->t('Fields'),
        '#options' => $options,
        '#multiple' => TRUE,
        '#size' => 3,
        '#default_value' => []
      ];

    }

    // Return the form portion to insert.
    return $element;
  }

  /**
   * Helper function to provide a container element.
   *
   * @param string $id
   *   The HTML ID the container should have.
   *
   * @return array
   *   An FAPI container element.
   */
  protected function getAjaxWrapperElement($id) {
    return [
      '#tree' => TRUE,
      '#type' => 'container',
      '#attributes' => [
        'id' => "{$id}-wrapper",
        'class' => [$id],
      ],
    ];
  }

}
