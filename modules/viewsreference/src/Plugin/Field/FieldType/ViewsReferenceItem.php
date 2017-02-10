<?php

namespace Drupal\viewsreference\Plugin\Field\FieldType;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraint;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'viewsreference' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 *
 * @FieldType(
 *   id = "viewsreference",
 *   label = @Translation("Views reference"),
 *   description = @Translation("A field reference to a view."),
 *   category = @Translation("Reference"),
 *   default_widget = "viewsreference_autocomplete",
 *   default_formatter = "viewsreference_formatter",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class ViewsReferenceItem extends EntityReferenceItem implements OptionsProviderInterface,
PreconfiguredFieldUiOptionsInterface {

  /**
   * {@inheritdoc}
   */

  public static function defaultStorageSettings() {
    return array(
      'target_type' => 'view',
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'plugin_types' => array('block' => 'block')
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['display_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Display Id'))
      ->setDescription(new TranslatableMarkup('The referenced display Id'));

    $properties['argument'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Argument'))
      ->setDescription(new TranslatableMarkup('An optional argument or contextual filter to apply to the View'));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup('Whether or not to include the View or Block title'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return parent::mainPropertyName();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $target_type = $field_definition->getSetting('target_type');
    $target_type_info = \Drupal::entityManager()->getDefinition($target_type);
    $properties = static::propertyDefinitions($field_definition)['target_id'];
    $schema['columns']['display_id'] = array(
      'description' => 'The ID of the display.',
      'type' => 'varchar_ascii',
      // If the target entities act as bundles for another entity type,
      // their IDs should not exceed the maximum length for bundles.
      'length' => $target_type_info->getBundleOf() ? EntityTypeInterface::BUNDLE_MAX_LENGTH : 255,
    );

    $schema['columns']['argument'] = array(
      'description' => 'An optional argument.',
      'type' => 'varchar_ascii',
      'length' => 255
    );

    $schema['columns']['title'] = array(
      'description' => 'Include title.',
      'type' => 'int',
      'length' => 11
    );

    $schema['indexes']['display_id'] = array('display_id');

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return parent::getConstraints();
    // Remove the 'AllowedValuesConstraint' validation constraint because entity
    // reference fields already use the 'ValidReference' constraint.
//    foreach ($constraints as $key => $constraint) {
//      if ($constraint instanceof AllowedValuesConstraint) {
//        unset($constraints[$key]);
//      }
//    }

  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Select widget has extra layer of items
    if (isset($values['target_id']) && is_array($values['target_id'])) {
      $values['target_id'] = $values['target_id'][0]['target_id'];
    }
    parent::setValue($values, FALSE);

  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return parent::getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    return parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Select widget requires this test
    if ($this->target_id == '') {
      return TRUE;
    }
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $types = \Drupal\views\Views::pluginList();
    $options = array();
    foreach ($types as $key => $type) {
      if ($type['type'] == 'display') {
        $options[str_replace('display:', '', $key)] = $type['title']->render();
      }
    }

    $default = $this->getSetting('plugin_types') !== NULL ? $this->getSetting('plugin_types') :
      $this->defaultFieldSettings()['plugin_types'];

    $form['plugin_types'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('View display plugins to allow'),
      '#default_value' => $default,
    ];

    return $form;
  }


  /**
   * Determines whether the item holds an unsaved entity.
   *
   * This is notably used for "autocreate" widgets, and more generally to
   * support referencing freshly created entities (they will get saved
   * automatically as the hosting entity gets saved).
   *
   * @return bool
   *   TRUE if the item holds an unsaved entity.
   */
  public function hasNewEntity() {
    return !$this->isEmpty() && $this->target_id === NULL && $this->entity->isNew();
  }


  /**
   * Render API callback: Processes the field settings form and allows access to
   * the form state.
   *
   * @see static::fieldSettingsForm()
   */
//  public static function fieldSettingsAjaxProcess($form, FormStateInterface $form_state) {
//    static::fieldSettingsAjaxProcessElement($form, $form);
//    return $form;
//  }

  /**
   * Adds entity_reference specific properties to AJAX form elements from the
   * field settings form.
   *
   * @see static::fieldSettingsAjaxProcess()
   */
//  public static function fieldSettingsAjaxProcessElement(&$element, $main_form) {
//    if (!empty($element['#ajax'])) {
//      $element['#ajax'] = array(
//        'callback' => array(get_called_class(), 'settingsAjax'),
//        'wrapper' => $main_form['#id'],
//        'element' => $main_form['#array_parents'],
//      );
//    }
//
//    foreach (Element::children($element) as $key) {
//      static::fieldSettingsAjaxProcessElement($element[$key], $main_form);
//    }
//  }

  /**
   * Render API callback: Moves entity_reference specific Form API elements
   * (i.e. 'handler_settings') up a level for easier processing by the
   * validation and submission handlers.
   *
   * @see _entity_reference_field_settings_process()
   */
//  public static function formProcessMergeParent($element) {
//    $parents = $element['#parents'];
//    array_pop($parents);
//    $element['#parents'] = $parents;
//    return $element;
//  }

  /**
   * Ajax callback for the handler settings form.
   *
   * @see static::fieldSettingsForm()
   */
//  public static function settingsAjax($form, FormStateInterface $form_state) {
//    return NestedArray::getValue($form, $form_state->getTriggeringElement()['#ajax']['element']);
//  }

  /**
   * Submit handler for the non-JS case.
   *
   * @see static::fieldSettingsForm()
   */
//  public static function settingsAjaxSubmit($form, FormStateInterface $form_state) {
//    $form_state->setRebuild();
//  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    return array();

  }

}
