<?php

namespace Drupal\animate_slider_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a new animate slider block.
 *
 * @Block(
 *   id = "animate_slider",
 *   admin_label = @Translation("Block: animate slider")
 * )
 */
class AnimateSliderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'category' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
	$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
	$tids = $storage->getQuery()
      ->condition('vid', 'slider')
      ->execute();
    $terms = $storage->loadMultiple($tids);
    $cat = array();
    foreach ($terms as $key => $term) {
      $cat[$key] = $term->toLink()->getText();
    }
    $form['category'] = array(
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => $cat,
      '#default_value' => $this->configuration['category'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['category'] = $form_state->getValue('category');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $storage->getQuery()
      ->condition('type', 'slider')
      ->condition('status', 1)
      ->condition('field_slider_type', $this->configuration['category'])
      ->execute();
    $nodes = $storage->loadMultiple($nids);

    $slide_config = array();
    $data = array();
    foreach ($nodes as $node) {
      $slide_config[] = array(
        '.animate-field-title' => array(
          'show' => 'bounceIn',
          'hide' => 'zoomOutUpBig',
          'delayShow' => 'delay1s',
        ),
        '.animate-field-body' => array(
          'show' => 'fadeInUpLeftLarge',
          'hide' => 'fadeOutDownBig',
          'delayShow' => 'delay1-5s',
        ),
        '.animate-field-link' => array(
          'show' => 'zoomInUpRightLarge',
          'hide' => 'fadeOutRightBig',
          'delayShow' => 'delay1-5s',
        ),
        'img#slideshow-image' => array(
          'show' => 'rollIn',
          'hide' => 'flipOutX',
          'delayShow' => 'delay2s',
        ),
      );
      $title = $node->title->value;
      $summary = $node->body->value;
      $image_storage = \Drupal::entityTypeManager()->getStorage('image_style');
	  $img = $image_storage->load('medium')->buildUrl($node->field_slide_image->entity->getFileUri());
      $node_uri = $node->toUrl('canonical');
      $node_link = \Drupal::l($this->t('Read more'), $node_uri);
      $data[] = array(
        'title' => $title,
        'summary' => $summary,
        'nodelink' => $node_link,
        'img' => $img,
      );
    }
    $js_config = array();
    $slide_id = $this->configuration['category'];
    $js_config[$slide_id] = array('config' => $slide_config);
    return array(
      '#title' => 'Animate slider',
      '#theme' => 'animate_slider_block',
      '#id' => $slide_id,
      '#rows' => $data,
      '#attached' => array(
        'library' => array(
          'animate_slider_block/jquery.animate.slider',
        ),
        'drupalSettings' => array(
          'sliderConfig' => array('config' => $slide_config),
          'sliderDetails' => $js_config,
        ),
      ),
    );
  }

}
