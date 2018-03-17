<?php

namespace Drupal\patch_revision\Entity;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Patch entity.
 *
 * @ingroup patch_revision
 *
 * @ContentEntityType(
 *   id = "patch",
 *   label = @Translation("Improvement"),
 *   handlers = {
 *     "view_builder" = "Drupal\patch_revision\PatchViewBuilder",
 *     "views_data" = "Drupal\patch_revision\Entity\PatchViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\patch_revision\Form\PatchForm",
 *       "apply" = "Drupal\patch_revision\Form\PatchApplyForm",
 *       "edit" = "Drupal\patch_revision\Form\PatchForm",
 *       "delete" = "Drupal\patch_revision\Form\PatchDeleteForm",
 *     },
 *     "access" = "Drupal\patch_revision\PatchAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\patch_revision\PatchHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "patch",
 *   admin_permission = "administer patch entities",
 *   list_cache_contexts = {
 *     "user.permissions",
 *     "languages",
 *     "timezone",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "created" = "created",
 *     "changed" = "changed",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "rtype" = "rtype",
 *     "rbundle" = "rbundle",
 *     "rid" = "rid",
 *     "rvid" = "rvid",
 *     "uid" = "uid",
 *     "patch" = "patch",
 *     "message" = "message",
 *   },
 *   links = {
 *     "canonical" = "/patch/{patch}",
 *     "apply-form" = "/patch/{patch}/apply",
 *     "edit-form" = "/patch/{patch}/edit",
 *     "delete-form" = "/patch/{patch}/delete",
 *   }
 * )
 */
class Patch extends ContentEntityBase {

  /**
   * The original entity the patch is abstracted from.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $originalEntity;

  /**
   * The user who created the patch.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $creator;

  /**
   * The Drupal entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity field map.
   *
   * @var array
   */
  protected $entityFieldMap;

  /**
   * The patch_revision.diff service as adapter to diff_match_patch.
   *
   * @var \Drupal\patch_revision\DiffService
   */
  public $diffService;

  /**
   * The patch_revision field patch manager.
   *
   * @var \Drupal\patch_revision\Plugin\FieldPatchPluginManager
   */
  private $pluginManager;

  /**
   * The revision_ids from the original entity.
   *
   * @var array
   */
  private $origRevisionIds;

  /**
   * The complete collection of all diffs stored in the entity.
   *
   * @var array
   */
  private $diff;

  /**
   * Returns lazy instance of field patch plugin manager.
   *
   * @return \Drupal\patch_revision\Plugin\FieldPatchPluginManager
   *   The field patch plugin manager.
   */
  public function getPluginManager() {
    if (!$this->pluginManager) {
      $this->pluginManager = \Drupal::service('plugin.manager.field_patch_plugin');
    }
    return $this->pluginManager;
  }

  /**
   * Returns the Diff entity.
   *
   * @return \Drupal\patch_revision\DiffService
   *   Returns lazy instance of the patch_revision.diff service.
   */
  public function getDiffService() {
    if (!$this->diffService) {
      $this->diffService = \Drupal::service('patch_revision.diff');
    }
    return $this->diffService;
  }

  /**
   * Returns lazy Entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManager
   *   Returns lazy instance of entity field manager.
   */
  protected function getEntityFieldManager() {
    if (!$this->entityFieldManager) {
      $this->entityFieldManager = \Drupal::service('entity_field.manager');
    }
    return $this->entityFieldManager;
  }

  /**
   * Returns lazy Entity field map.
   *
   * @return array
   *   The complete field map.
   */
  protected function getEntityFieldMap() {
    if (!$this->entityFieldMap) {
      $this->entityFieldMap = $this->getEntityFieldManager()->getFieldMap();
    }
    return $this->entityFieldMap;
  }

  /**
   * Field type property.
   *
   * @param string $field_name
   *   The field machine name.
   *
   * @return string
   *   Returns field_type from field map of entity type.
   */
  public function getEntityFieldType($field_name) {
    $entity_id = $this->entityKeys['rtype'];
    $map = $this->getEntityFieldMap();
    return (isset($map[$entity_id][$field_name]))
      ? $map[$entity_id][$field_name]['type']
      : '';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields[$entity_type->getKey('created')] = BaseFieldDefinition::create('created')
      ->setLabel(t('Added on'))
      ->setDescription(t('The time that the patch was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the node was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields[$entity_type->getKey('uuid')] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setReadOnly(TRUE);

    $fields[$entity_type->getKey('status')] = BaseFieldDefinition::create('integer')
      ->setLabel(t('status'))
      ->setDescription(t('The status of the patch.'))
      ->setSetting('size', 'small');

    $fields[$entity_type->getKey('rtype')] = BaseFieldDefinition::create('string')
      ->setLabel(t('type of referred node.'))
      ->setRevisionable(FALSE)
      ->setDefaultValue('');

    $fields[$entity_type->getKey('rbundle')] = BaseFieldDefinition::create('string')
      ->setLabel(t('bundle of referred node'))
      ->setRevisionable(FALSE)
      ->setDefaultValue('');

    $fields[$entity_type->getKey('rid')] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Referred node'))
      ->setDescription(t('The referred node of the patch.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'node');

    $fields[$entity_type->getKey('rvid')] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Node version id'))
      ->setReadOnly(TRUE)
      ->setDefaultValue('')
      ->setSetting('unsigned', TRUE);

    $fields[$entity_type->getKey('uid')] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Creator'))
      ->setDescription(t('The creator of the patch.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'user');

    $fields[$entity_type->getKey('patch')] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Patch'))
      ->setReadOnly(TRUE)
      ->setRevisionable(FALSE)
      ->setDefaultValue('');

    $fields[$entity_type->getKey('message')] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('');

    return $fields;
  }

  /**
   * Returns patch for all changed fields.
   *
   * @return array
   *   Returns conten of patch field.
   */
  public function getPatchField() {
    if (!$this->diff) {
      $patch = $this->get('patch')->getValue();
      $this->diff = (count($patch)) ? $patch[0] : [];
    }
    return $this->diff;
  }

  /**
   * Returns patch for a single field.
   *
   * @param string $field_name
   *   The field name to return.
   *
   * @return array
   *   The field value.
   */
  public function getPatchValue($field_name = '') {
    $patch = $this->getPatchField();
    if (isset($patch[$field_name])) {
      return $patch[$field_name];
    }
    else {
      return [];
    }
  }

  /**
   * Returns all revision_ids for an entity.
   *
   * @return array
   *   Returns complete array of revision ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getOrigRevisionIds() {
    if (!$this->origRevisionIds) {
      $entity = $this->originalEntity();
      $entity_type = $entity->getEntityType();
      if ($entity_type->isRevisionable()) {
        $entity_type_id = $entity_type->id();
        $storage = $this->entityTypeManager()->getStorage($entity_type_id);
        /* @var \Drupal\node\NodeStorageInterface $storage */
        $this->origRevisionIds = $storage->revisionIds($entity);
      }
    }
    return $this->origRevisionIds;
  }

  /**
   * Returns the referred entity.
   *
   * @var string|int $version
   *   If method returns current or the latest (possibly unpublished) revision.
   *   Accepted values are 'current', 'latest' or a revision_id as '123'.
   *
   * @return \Drupal\node\NodeInterface|false
   *   Returns the original entity.
   */
  public function originalEntity() {
    if (!isset($this->originalEntity)) {
      /** @var \Drupal\node\NodeInterface[] $orig_entity */
      $orig_entity = $this->get('rid')->referencedEntities();
      $this->originalEntity = reset($orig_entity);
    }
    return $this->originalEntity;
  }

  /**
   * Returns a revision referred entity or FALSE if none exists.
   *
   * @var string|int $version
   *   If method returns current or the latest (possibly unpublished) revision.
   *   Accepted values are 'current', 'latest', 'origin' or a rid as '123'.
   *
   * @return \Drupal\node\NodeInterface|false
   *   Returns a original node revision.
   */
  public function originalEntityRevision($revision = 'current') {
    $entity = $this->originalEntity();
    $revision_ids = $this->getOrigRevisionIds();

    switch ($revision) {
      case 'current':
        return $entity;

      case 'latest':
        $revision_id = end($revision_ids);
        break;

      case 'origin':
        $revision_id = (int) $this->get('rvid')->getString();
        break;

      default:
        $revision_id = in_array((int) $revision, $revision_ids)
          ? (int) $revision
          : FALSE;
    }

    try {
      if ($revision_id) {
        $entity_type = $entity->getEntityTypeId();
        return $this->entityTypeManager()->getStorage($entity_type)->loadRevision($revision_id);
      }
      else {
        return FALSE;
      }
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return FALSE;
    }
  }

  /**
   * Returns a revision referred entity or FALSE if none exists.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   Returns the original entity in that revision the patch was created from.
   */
  public function originalEntityRevisionOld() {
    return $this->originalEntityRevision('origin');
  }

  /**
   * Returns the Creator user.
   *
   * @return \Drupal\user\Entity\User|false
   *   Returns the creator user.
   */
  public function getCreator() {
    if (!isset($this->creator)) {
      /** @var \Drupal\user\Entity\User[] $creator */
      $creators = $this->get('uid')->referencedEntities();
      $this->creator = reset($creators) ?: '';
    }
    return $this->creator;
  }

  /**
   * Returns the label belongs to the field type.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   Returns the label belongs to the field type.
   */
  public function getOrigFieldLabel($field_name) {
    if ($orig_entity = $this->originalEntity()) {
      return $orig_entity->getFieldDefinition($field_name)->getLabel();
    }
    else {
      return ucfirst(str_replace('_', ' ', $field_name));
    }
  }

  /**
   * Returns collection of important data to display in head of a patch entity.
   *
   * @return array
   *   Returns data collection for header.
   */
  public function getViewHeaderData() {
    $creator = $this->getCreator();
    $orig_entity = $this->originalEntity();
    $created = $this->get('created')->getValue();
    $created = reset($created);
    try {
      $link = $orig_entity->toLink();
    }
    catch (EntityMalformedException $exception) {
      drupal_set_message($exception->getMessage());
    }
    return [
      'created' => ($created) ? $created['value'] : NULL,
      'creator' => ($creator) ? $creator->toLink()->toString() : FALSE,
      'log_message' => $this->get('message')->getString() ?: FALSE,
      'orig_id' => $this->get('rid')->getString(),
      'orig_type' => ($orig_entity) ? $orig_entity->type->entity->label() : FALSE,
      'orig_title' => (isset($link) && $link instanceof Link) ? $link->toString() : FALSE,
    ];
  }

}
