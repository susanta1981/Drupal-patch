<?php

/**
 * @file
 * Contains \Drupal\captcha_keypad\Form\CaptchaKeypadSettingsForm.
 */

namespace Drupal\captcha_keypad\Form;

use Drupal\comment\Entity\CommentType;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class CaptchaKeypadSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'captcha_keypad_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('captcha_keypad.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['captcha_keypad.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['captcha_keypad_code_size'] = [
      '#type' => 'textfield',
      '#title' => t('Code size'),
      '#description' => t('Size of the code.'),
      '#size' => 2,
      '#maxlength' => 2,
      '#default_value' => \Drupal::config('captcha_keypad.settings')->get('captcha_keypad_code_size'),
      '#required' => TRUE,
    ];

    $form['captcha_keypad_shuffle_keypad'] = [
      '#type' => 'checkbox',
      '#title' => t('Shuffle keypad'),
      '#description' => t('Selecting this option will make the keys appear in random order.'),
      '#default_value' => \Drupal::config('captcha_keypad.settings')->get('captcha_keypad_shuffle_keypad'),
    ];

    $form_ids = [];
    if (\Drupal::moduleHandler()->moduleExists('contact')) {
      $form_ids['contact_message_feedback_form'] = t('Site: contact');
      $form_ids['contact_message_personal_form'] = t('User: contact');
    }
    if (\Drupal::moduleHandler()->moduleExists('forum')) {
      $form_ids['comment_comment_forum_form'] = t('Forum: comment');
    }
    if (\Drupal::moduleHandler()->moduleExists('user')) {
      $form_ids['user_register_form'] = t('User: register');
      $form_ids['user_pass'] = t('User: Forgot password');
      $form_ids['user_login_form'] = t('User: Login');
      $form_ids['user_login_block'] = t('User: Login block');
    }
    $comment_types = \Drupal\comment\Entity\CommentType::loadMultiple();
    foreach ($comment_types as $id => $item) {
      $form_ids['comment_' . $id . '_form'] = t('Comment: :item', array(':item' => $item->getDescription()));
    }

    $form['captcha_keypad_forms'] = [
      '#type' => 'checkboxes',
      '#title' => t('Forms'),
      '#options' => $form_ids,
      '#default_value' => \Drupal::config('captcha_keypad.settings')->get('captcha_keypad_forms'),
      '#description' => t('Select which forms to add captcha keypad.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('captcha_keypad.settings')->set('captcha_keypad_forms', $form_state->getValue([
      'captcha_keypad_forms'
      ]))->save();
  }

  public function _submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

  }

}
