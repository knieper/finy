<?php

namespace Drupal\viewsreference\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\viewsreference\Plugin\Field\FieldWidget\ViewsReferenceTrait;

/**
 * Plugin implementation of the 'options_select' widget.
 *
 * @FieldWidget(
 *   id = "viewsreference_select",
 *   label = @Translation("Views Reference Select list"),
 *   description = @Translation("An autocomplete views select list field."),
 *   field_types = {
 *     "viewsreference"
 *   }
 * )
 */
class ViewsReferenceSelectWidget extends OptionsSelectWidget {

  use ViewsReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $select_element['target_id'] = parent::formElement($items, $delta, $element, $form, $form_state);

    $select_element = $this->fieldElement($select_element, $items, $delta);

    $select_element['target_id']['#multiple'] = FALSE;
    $select_element['target_id']['#empty_option'] = t('- None -');

    return $select_element;
  }


}
