<?php

namespace Drupal\inmail_test\Plugin\inmail\Deliverer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\inmail\Plugin\inmail\Deliverer\DelivererBase;
use Drupal\inmail\Plugin\inmail\Deliverer\FetcherBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delivers a dummy message and counts invocations.
 *
 * @Deliverer(
 *   id = "test_fetcher",
 *   label = @Translation("Test")
 * )
 */
class TestFetcher extends FetcherBase implements ContainerFactoryPluginInterface {

  /**
   * Injected site state.
   *
   * The following state keys are used with the test deliverer:
   *   - inmail.test.deliver_count: Number of times that fetch() has been
   *     invoked.
   *   - inmail.test.deliver_remaining: Cached number of remaining messages.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The number of remaining messages.
   *
   * Unlike the state variable inmail.test.deliver_remaining, this static
   * property models the actual number at a remote location.
   *
   * @var int
   */
  protected static $remaining = 100;

  /**
   * The Unix timestamp of the last check.
   *
   * @var int
   */
  protected static $last_checked;

  /**
   * Constructs a TestFetcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('state'));
  }

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    // Increment invocation count.
    $count = $this->state->get('inmail.test.deliver_count') + 1;
    $this->state->set('inmail.test.deliver_count', $count);

    // Decrement the remaining counter.
    static::$remaining--;

    // Return one minimal message.
    return array("Subject: Dummy message $count\n\nFoo");
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {
    return $this->state->get('inmail.test.deliver_remaining');
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    $this->state->set('inmail.test.deliver_remaining', static::$remaining);
    $this->setLastCheckedTime(REQUEST_TIME);
  }

  /**
   * {@inheritdoc}
   */
  public function setLastCheckedTime($timestamp) {
    static::$last_checked = $timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastCheckedTime() {
    return static::$last_checked;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
