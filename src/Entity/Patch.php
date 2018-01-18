<?php

namespace Drupal\patch_revision\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\patch_revision\DiffService;
use Drupal\patch_revision\Plugin\FieldPatchPluginInterface;
use Drupal\user\Entity\User;

/**
 * Defines the Patch entity.
 *
 * @ingroup patch_revision
 *
 * @ContentEntityType(
 *   id = "patch",
 *   label = @Translation("Patch"),
 *   handlers = {
 *     "view_builder" = "Drupal\patch_revision\PatchViewBuilder",
 *     "list_builder" = "Drupal\patch_revision\PatchListBuilder",
 *     "views_data" = "Drupal\patch_revision\Entity\PatchViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\patch_revision\Form\PatchForm",
 *       "add" = "Drupal\patch_revision\Form\PatchForm",
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
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "rid" = "rid",
 *     "rvid" = "rvid",
 *     "uid" = "uid",
 *     "patch" = "patch",
 *     "message" = "message",
 *   },
 *   links = {
 *     "canonical" = "/patch/{patch}",
 *     "add-form" = "/patch/add",
 *     "edit-form" = "/patch/{patch}/edit",
 *     "delete-form" = "/patch/{patch}/delete",
 *     "collection" = "/patch",
 *   }
 * )
 */
class Patch extends ContentEntityBase {

  /**
   * @var EntityInterface
   */
  private $originalEntity;

  /**
   * @var DiffService
   */
  private $diffService;

  /**
   * @var User
   */
  private $creator;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
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

    $fields[$entity_type->getKey('uuid')] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setReadOnly(TRUE);

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
      ->setDefaultValue([]);


    $fields[$entity_type->getKey('message')] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => [
          'rows' => 3,
        ],
      ]);

    return $fields;
  }

  /**
   * Returns the referred entity.
   *
   * @return \Drupal\node\NodeInterface|FALSE
   */
  public function originalEntity() {
    if (!isset($this->originalEntity)) {
      /** @var \Drupal\node\NodeInterface[] $orig_entity */
      $orig_entity = $this->get('rid')->referencedEntities();
      $this->originalEntity = count($orig_entity) ? $orig_entity[0] : FALSE;
    }
    return $this->originalEntity;
  }

  /**
   * Returns the Creator user.
   *
   * @return \Drupal\user\Entity\User|FALSE
   */
  public function getCreator() {
    if (!isset($this->creator)) {
      /** @var \Drupal\user\Entity\User[] $creator */
      $creator = $this->get('uid')->referencedEntities();
      $this->creator = count($creator) ? $creator[0] : FALSE;
    }
    return $this->creator;
  }

  /**
   * Returns the Diff entity.
   *
   * @return DiffService
   */
  public function getDiffService() {
    if (!isset($this->diffService)) {
      $this->diffService = \Drupal::service('patch_revision.diff');
    }
    return $this->diffService;
  }

  /**
   * Returns the plugin belongs to the field type.
   *
   * @param $field_name
   *   The field name mashine readable.
   *
   * @return FieldPatchPluginInterface|FALSE
   */
  public function getPatchPluginFromOrigFieldName($field_name) {
    if ($orig_entity = $this->originalEntity()) {
      $field_type = $orig_entity->getFieldDefinition($field_name)->getType();
      return $this->getDiffService()->getPluginFromFieldType($field_type);
    }
    else return FALSE;
  }


  /**
   * Returns the label belongs to the field type.
   *
   * @return TranslatableMarkup|string
   */
  public function getOrigFieldLabel($field_name) {
    if ($orig_entity = $this->originalEntity()) {
      return $orig_entity->getFieldDefinition($field_name)->getLabel();
    }
    else return '';
  }

}
