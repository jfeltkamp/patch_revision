<?php

namespace Drupal\patch_revision\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;
use Drupal\patch_revision\Plugin\FieldPatchPluginManager;
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
   * FieldPatchPluginManager.
   *
   * @var \Drupal\patch_revision\Plugin\FieldPatchPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a new PatchRevisionConfig object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityTypeManager $entity_type_manager,
      EntityFieldManager $entity_field_manager,
      FieldPatchPluginManager $plugin_manager
    ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->pluginManager = $plugin_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field_patch_plugin')
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
   * @var $patchable_fields \Drupal\Core\Field\FieldDefinitionInterface[]
   * @return array
   *
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
    /** @var NodeTypeInterface[] $node_types */
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $options = [];
    $form['bundle_select'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-publication',
      ];
    $default_values = $config->get('node_types');
    foreach ($node_types as $name => $node_type) {
      $options[$name] = $node_type->label();

      $form[] = $this->getBundleSelector($node_type, (0 === $default_values[$node_type->id()]));
    }

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node Types'),
      '#description' => $this->t('Check node types where to provide a patch functionality.'),
      '#options' => $options,
      '#default_value' => $default_values,
      '#weight' => -1,
    ];


    $form['tab_general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#description' => $this->t('<h3>General settings</h3>'),
      '#group' => 'bundle_select',
    ];
    $form['tab_general']['general_excluded_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('General excluded fields.'),
      '#default_value' => implode(PHP_EOL, $config->get('general_excluded_fields')),
      '#description' => $this->t('Insert machine readable field_names one-per-line to exclude from patching. In particular, fields are excluded here that are not contents, but are valuable for the information processing and presentation logic.'),
    ];


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
    $config = $this->config('patch_revision.config');
    $config->set('node_types', $form_state->getValue('node_types'));
    $config->set('general_excluded_fields', preg_split("/\\r\\n|\\r|\\n/", $form_state->getValue('general_excluded_fields')));

    foreach ($form_state->getValues() as $key => $value) {
      if (preg_match('/^bundle_[a-z_]+_fields$/', $key)) {
        $config->set($key, $value);
      }
    }
    $config->save();
  }

  /**
   * Returns additional form elements after selecting the desired bundle.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The form in it's current state.
   * @param bool $disabled
   *   Form Element disabled.
   *
   * @return array
   *   The form elements to insert into the form.
   */
  protected function getBundleSelector($node_type, $disabled = TRUE) {
    $config = $this->config('patch_revision.config');
    $options = $this->getFieldOptions($this->pluginManager->getPatchableFields($node_type, TRUE));
    $element['tab_' . $node_type->id()] = [
      '#type' => 'details',
      '#title' => 'Node type: ' . $node_type->label(),
      '#description' => $this->t('<h3>Patch config for node type "@type"</h3><p>@desc</p>' , [
        '@type' => $node_type->label(),
        '@desc' => $node_type->getDescription(),
        ]),
      '#group' => 'bundle_select',
    ];
    $element['tab_' . $node_type->id()]['bundle_' . $node_type->id() . '_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded fields from Patch Revision in @type', ['@type' => $node_type->label()]),
      '#options' => $options,
      '#default_value' => $config->get('bundle_' . $node_type->id() . '_fields'),
      '#disabled' => $disabled,
      '#description' => $disabled
        ? $this->t('<div class="messages messages--warning">Enable the node type above and save - before you can change field configuration here.</div>')
        : $this->t('Select fields you want to exclude from patches. Changes in excluded fields will not be saved in the patch.') ,
    ];

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
