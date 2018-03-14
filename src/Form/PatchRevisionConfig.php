<?php

namespace Drupal\patch_revision\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * FieldPatchPluginManager.
   *
   * @var CacheTagsInvalidatorInterface
   */
  protected $cacheInvalidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManager $entity_type_manager,
    EntityFieldManager $entity_field_manager,
    FieldPatchPluginManager $plugin_manager,
    CacheTagsInvalidatorInterface $cache_invalidator
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->pluginManager = $plugin_manager;
    $this->cacheInvalidator = $cache_invalidator;
  }

  /**
   * @param ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field_patch_plugin'),
      $container->get('cache_tags.invalidator')
    );
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
  protected function getEditableConfigNames() {
    return [
      'patch_revision.config',
    ];
  }

  /**
   * Returns formatted field options to select.
   *
   * @var $patchable_fields \Drupal\Core\Field\FieldDefinitionInterface[]
   *   Array with pre selected field definitions.
   * @return array
   *   Default values for form select/checkboxes widget.
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('patch_revision.config');
    /** @var NodeTypeInterface[] $node_types */
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $options = [];
    $form['bundle_select'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-publication',
    ];
    $default_values = $config->get('node_types') ?: [];
    foreach ($node_types as $name => $node_type) {
      $options[$name] = $node_type->label();
      $disabled = (!isset($default_values[$node_type->id()]) || 0 === $default_values[$node_type->id()]);

      $form[] = $this->getBundleSelector($node_type, $disabled);
    }

    $form['tab_general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#description' => $this->t('<h3>General settings</h3>'),
      '#group' => 'bundle_select',
      '#weight' => -10,
    ];

    $form['tab_general']['node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node Types'),
      '#description' => $this->t('Check node types where to provide a patch functionality.'),
      '#options' => $options,
      '#default_value' => $default_values,
      '#weight' => 5,
    ];

    $form['tab_general']['general_excluded_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('General excluded fields.'),
      '#default_value' => implode(PHP_EOL, $config->get('general_excluded_fields')),
      '#description' => $this->t('Insert machine readable field_names one-per-line to exclude from patching. In particular, fields are excluded here that are not contents, but are valuable for the information processing and presentation logic.'),
      '#weight' => 10,
      ];

    $form['tab_general']['enable_checkbox_node_form'] = [
      '#type' => 'checkbox',
      '#title' => t('Display Checkbox "Create patch from changes" on Node forms.'),
      '#default_value' => $config->get('enable_checkbox_node_form'),
      '#group' => 'tab_general',
      '#description' => t('Let user decide, if he wants to create a patch or save the node regular.'),
      '#weight' => 15,
    ];

    $form['tab_general']['log_message_required'] = [
      '#type' => 'checkbox',
      '#title' => t('Set log message required.'),
      '#default_value' => $config->get('log_message_required'),
      '#group' => 'tab_general',
      '#description' => t('If checked user can not submit the node form without a log message.'),
      '#weight' => 20,
    ];

    $form['tab_general']['log_message_title'] = [
      '#type' => 'textfield',
      '#title' => t('Log message title.'),
      '#default_value' => $config->get('log_message_title'),
      '#group' => 'tab_general',
      '#description' => t('The default title of log message ist "Revision log message" what is a bit confusing, because it is also used for patch log messages.'),
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => 25,
    ];

    $image_styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    $options = [];
    foreach ($image_styles as $id => $image_style) {
      $options[$id] = $image_style->label();
    }
    $form['tab_general']['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#description' => $this->t('Image style to use in patch detail view and apply form.'),
      '#options' => $options,
      '#default_value' => $config->get('image_style') ?: 'thumbnail',
      '#weight' => 30,
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
    $config->set('enable_checkbox_node_form', $form_state->getValue('enable_checkbox_node_form'));
    $config->set('log_message_required', $form_state->getValue('log_message_required'));
    $config->set('log_message_title', $form_state->getValue('log_message_title'));
    $config->set('image_style', $form_state->getValue('image_style'));

    foreach ($form_state->getValues() as $key => $value) {
      if (preg_match('/^bundle_[a-z_]+_fields$/', $key)) {
        $config->set($key, $value);
      }
    }
    $config->save();

    $this->cacheInvalidator->invalidateTags(['local-tasks-node-list-cache-tag']);
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
    $options = $this->getFieldOptions($this->pluginManager->getPatchableFields($node_type->id(), TRUE));
    $element['tab_' . $node_type->id()] = [
      '#type' => 'details',
      '#title' => 'Node type: ' . $node_type->label(),
      '#description' => $this->t('<h3>Patch config for node type "@type"</h3><p>@desc</p>' , [
        '@type' => $node_type->label(),
        '@desc' => $node_type->getDescription(),
        ]),
      '#group' => 'bundle_select',
    ];
    $default_value = $config->get('bundle_' . $node_type->id() . '_fields') ?: [];
    $element['tab_' . $node_type->id()]['bundle_' . $node_type->id() . '_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded fields from Patch Revision in @type', ['@type' => $node_type->label()]),
      '#options' => $options,
      '#default_value' => $default_value,
      '#disabled' => $disabled,
      '#description' => $disabled
        ? $this->t('<div class="messages messages--warning">Enable the node type in "General settengs" and save - before you can change field configuration here.</div>')
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
