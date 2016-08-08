<?php

namespace Drupal\inmail\Plugin\inmail\Deliverer;

use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for mail fetchers.
 *
 * This provides dumb implementations for most methods, but leaves ::fetch() and
 * some configuration methods abstract.
 *
 * @ingroup deliverer
 */
abstract class FetcherBase extends DelivererBase implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    // Merge with defaults.
    parent::__construct($configuration + $this->defaultConfiguration(), $plugin_id, $plugin_definition);
  }

  /**
   * Update the number of remaining messages to fetch.
   *
   * @param int $count
   *   The number of remaining messages.
   */
  protected function setCount($count) {
    \Drupal::state()->set($this->makeStateKey('remaining'), $count);
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {
    return \Drupal::state()->get($this->makeStateKey('remaining'));
  }

  /**
   * {@inheritdoc}
   */
  public function setLastCheckedTime($timestamp) {
    \Drupal::state()->set($this->makeStateKey('last_checked'), $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastCheckedTime() {
    $config_id = $this->getConfiguration()['config_id'];
    return \Drupal::state()->get($this->makeStateKey('last_checked'));
  }

  /**
   * Returns a state key appropriate for the given state property.
   *
   * @param string $key
   *   Name of key.
   *
   * @return string
   *   An appropriate name for a state property of the deliverer config
   *   associated with this fetcher.
   */
  protected function makeStateKey($key) {
    $config_id = $this->getConfiguration()['config_id'];
    return "inmail.deliverer.$config_id.$key";
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // No validation by default.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityForm $form_object */
    $form_object = $form_state->getFormObject();
    $this->configuration['config_id'] = $form_object->getEntity()->id();

    // Reset state.
    $this->setLastCheckedTime(NULL);
    $this->setCount(NULL);
  }

  /**
   * Handles submit call of "Test connection" button.
   */
  public function submitTestConnection(array $form, FormStateInterface $form_state) {
    throw new \Exception('Implement submitTestConnection() method in a subclass.');
  }

  /**
   * Adds a "Test connection" button to a form.
   *
   * @return array
   *   A form array containing "Test connection" button.
   */
  public function addTestConnectionButton() {
    $form['test_connection'] = array(
      '#type' => 'submit',
      '#value' => t('Test connection'),
      '#submit' => array(
        array($this, 'submitTestConnection'),
      ),
      '#executes_submit_callback' => TRUE,
      '#ajax' => array(
        'callback' => '::getPluginContainerFormChild',
        'wrapper' => 'inmail-plugin',
      ),
    );

    return $form;
  }

}
