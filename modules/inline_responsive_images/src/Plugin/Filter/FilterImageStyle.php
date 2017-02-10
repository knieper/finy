<?php

/**
 * @file
 * Contains \Drupal\inline_responsive_images\Plugin\Filter\FilterImageStyle.
 */

namespace Drupal\inline_responsive_images\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Annotation\Translation;
use Drupal\filter\Annotation\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to render inline images as image styles.
 *
 * @Filter(
 *   id = "filter_imagestyle",
 *   module = "inline_responsive_images",
 *   title = @Translation("Display image styles"),
 *   description = @Translation("Uses the data-image-style attribute on &lt;img&gt; tags to display image styles."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterImageStyle extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = \Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple();
    $form['image_styles'] = array(
      '#type' => 'markup',
      '#markup' => 'Select the image styles that are avaliable in the editor',
    );
    foreach($image_styles as $style){
      $form['image_style_'.$style->id()] = array(
        '#type' => 'checkbox',
        '#title' => $style->label(),
        '#default_value' => (isset($this->settings['image_style_'.$style->id()]) ? $this->settings['image_style_'.$style->id()] : 0),
      );      
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $search = array();
    $replace = array();

    if (stristr($text, 'data-image-style') !== FALSE && stristr($text, 'data-responsive-image-style') == FALSE) {
      $image_styles = \Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple();

      $dom = HTML::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-entity-type="file" and @data-entity-uuid and @data-image-style]') as $node) {
        $file_uuid = $node->getAttribute('data-entity-uuid');
        $node->removeAttribute('data-entity-uuid');
        $image_style_id = $node->getAttribute('data-image-style');
        $node->removeAttribute('data-image-style');

        // If the image style is not a valid one, then don't transform the HTML.
        if (empty($file_uuid) || !in_array($image_style_id, array_keys($image_styles))) {
          continue;
        }

        // Retrieved matching file in array for the specified uuid.
        $matching_files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uuid' => $file_uuid]);
        $file = reset($matching_files);

        // Determine width/height of the source image.
        $width = $height = NULL;
        $image = \Drupal::service('image.factory')->get($file->getFileUri());

        // Stop further element processing, if it's not a valid image.
        if (!$image->isValid()) continue;

        $width = $image->getWidth();
        $height = $image->getHeight();

        // Make sure all non-regenerated attributes are retained.
        $node->removeAttribute('width');
        $node->removeAttribute('height');
        $node->removeAttribute('src');
        $attributes = array();
        for ($i = 0; $i < $node->attributes->length; $i++) {
          $attr = $node->attributes->item($i);
          $attributes[$attr->name] = $attr->value;
        }

        // Re-render as an image style.
        $image = array(
          '#theme' => 'image_style',
          '#style_name' => $image_style_id,
          '#uri' => $file->getFileUri(),
          '#width' => $width,
          '#height' => $height,
          '#attributes' => $attributes,
        );
        
        $altered_html = \Drupal::service('renderer')->render($image);

        // Load the altered HTML into a new DOMDocument and retrieve the element.
        $updated_node = HTML::load($altered_html)->getElementsByTagName('body')
          ->item(0)
          ->childNodes
          ->item(0);

        // Import the updated node from the new DOMDocument into the original
        // one, importing also the child nodes of the updated node.
        $updated_node = $dom->importNode($updated_node, TRUE);
        // Finally, replace the original image node with the new image node!
        $node->parentNode->replaceChild($updated_node, $node);
      }

      return new FilterProcessResult(HTML::serialize($dom));
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      $image_styles = \Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple();
      $list = '<code>' . implode('</code>, <code>', array_keys($image_styles)) . '</code>';
      return t('
        <p>You can display images using site-wide styles by adding a <code>data-image-style</code> attribute, whose values is one of the image style machine names: !image-style-machine-name-list.</p>', array('!image-style-machine-name-list' => $list));
    }
    else {
      return t('You can display images using site-wide styles by adding a data-image-style attribute.');
    }
  }
}