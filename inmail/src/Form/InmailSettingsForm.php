<?php

namespace Drupal\inmail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for general Inmail configuration.
 *
 * @ingroup processing
 */
class InmailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inmail_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['inmail.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('inmail.settings');

    $form['return_path'] = array(
      '#title' => $this->t('Return-Path address'),
      '#type' => 'email',
      '#description' => $this->t('Normally the site email address (%site_mail) is used for the <code>Return-Path</code> header in outgoing messages. You can use this field to set another, dedicated address, or leave it empty to use the site email address. Note: VERP is not applied on messages with multiple recipients.',
          ['%site_mail' => \Drupal::config('system.site')->get('mail')]),
      // Setting #element_validate breaks merging with defaults, so specify the
      // standard email validation explicitly.
      '#element_validate' => ['::validateReturnPath', ['\Drupal\Core\Render\Element\Email', 'validateEmail']],
      '#default_value' => $config->get('return_path'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('inmail.settings')
      ->set('return_path', $form_state->getValue('return_path'))
      ->save();
  }

  /**
   * Validates the Return-Path value.
   */
  public function validateReturnPath(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $address = $element['#value'];

    // Make sure the given address works with the VERP parse rules.
    if (preg_match('/\+.*@/', $address)) {
      $form_state->setError($element, $this->t('The address may not contain a <code>+</code> character.'));
    }
  }
}
