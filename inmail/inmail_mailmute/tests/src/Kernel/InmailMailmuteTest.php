<?php

namespace Drupal\Tests\inmail_mailmute\Kernel;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\inmail\BounceAnalyzerResult;
use Drupal\inmail\DSNStatus;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Entity\HandlerConfig;
use Drupal\inmail\MIME\Header;
use Drupal\inmail\MIME\Message;
use Drupal\inmail\ProcessorResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the Mailmute message handler.
 *
 * @group inmail
 */
class InmailMailmuteTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'inmail_mailmute',
    'inmail_test',
    'inmail',
    'mailmute',
    'user',
    'field',
    'system',
  ];

  /**
   * A user matching the recipient in the test messages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installConfig(['inmail', 'mailmute', 'inmail_mailmute', 'system']);
  }

  /**
   * Process messages and test that the send state is transitioned correctly.
   */
  public function testProcessAndTriggerSendStateTransition() {
    /** @var \Drupal\inmail\MessageProcessorInterface $processor */
    $processor = \Drupal::service('inmail.processor');

    // @todo Extend sample message collection https://www.drupal.org/node/2381029
    $cases = array(
      // Normal message should not trigger mute.
      'normal.eml' => 'send',
      // "Mailbox full" bounce should trigger counting.
      'full.eml' => 'inmail_counting',
      // "No such user" bounce should trigger mute.
      'nouser.eml' => 'inmail_invalid_address',
      // "Access denied" bounce should trigger mute.
      'accessdenied.eml' => 'inmail_invalid_address',
    );

    foreach ($cases as $filename => $expected) {
      $this->resetUser();
      $raw = $this->getMessageFileContents($filename);

      // Let magic happen.
      $processor->process($raw, DelivererConfig::create(array('id' => 'test')));

      // Reload user.
      $this->user = User::load($this->user->id());

      // Check the outcome.
      $this->assertEqual($this->user->sendstate->plugin_id, $expected);
      // @todo Test more than plugin ID: status code, reason, date.
    }
  }

  /**
   * Test the "Persistent send" state.
   */
  public function testPersistentSendstate() {
    /** @var \Drupal\mailmute\SendStateManagerInterface $sendstate_manager */
    $sendstate_manager = \Drupal::service('plugin.manager.sendstate');
    $this->resetUser();

    // Some bounce result statuses to test.
    /** @var \Drupal\inmail\DSNStatus[] $statuses */
    $statuses = array(
      // Not a bounce.
      new DSNStatus(2, 0, 0),
      // Soft bounce (temporarily unavailable).
      new DSNStatus(4, 0, 0),
      // Hard bounce (unexisting addres etc).
      new DSNStatus(5, 0, 0),
    );

    foreach ($statuses as $status) {
      // Set the user's state to Persistent send.
      $sendstate_manager->transition($this->user->getEmail(), 'persistent_send');

      // Invoke the handler.
      $processor_result = new ProcessorResult();
      /** @var \Drupal\inmail\BounceAnalyzerResult $result */
      $result = $processor_result->ensureAnalyzerResult(BounceAnalyzerResult::TOPIC, BounceAnalyzerResult::createFactory());
      $result->setStatusCode($status);
      /** @var \Drupal\inmail\Entity\HandlerConfig $handler_config */
      $handler_config = \Drupal::entityManager()->getStorage('inmail_handler')->load('mailmute');
      /** @var \Drupal\inmail\Plugin\inmail\Handler\HandlerInterface $handler */
      $handler = \Drupal::service('plugin.manager.inmail.handler')->createInstance($handler_config->getPluginId(), $handler_config->getConfiguration());
      $handler->invoke(new Message(new Header(), ''), $processor_result, 'test');

      // Check that the state did not change.
      $new_state = $sendstate_manager->getState($this->user->getEmail());
      $message = SafeMarkup::format('Status %status results in state %state', array('%status' => $status->getCode(), '%state' => $new_state->getPluginId()));
      $this->assertEqual($new_state->getPluginId(), 'persistent_send', $message);
    }
  }

  /**
   * Test the counting of soft bounces.
   */
  public function testBounceCounting() {
    /** @var \Drupal\inmail\MessageProcessorInterface $processor */
    $processor = \Drupal::service('inmail.processor');
    $this->resetUser();

    // Initial state is "send".
    $this->assertEqual($this->user->sendstate->plugin_id, 'send');

    // Set soft_threshold to non-default value.
    /** @var \Drupal\inmail\Entity\HandlerConfig $handler_config */
    $handler_config = HandlerConfig::load('mailmute');
    $handler_config->setConfiguration(array('soft_threshold' => 3))->save();

    // Process the configured number of bounces.
    for ($count = 1; $count < 3; $count++) {
      // Process a soft bounce from the user.
      $raw = $this->getMessageFileContents('full.eml');
      $processor->process($raw, DelivererConfig::create(array('id' => 'test')));

      // Reload user and check the count.
      $this->user = User::load($this->user->id());
      $this->assertEqual($this->user->sendstate->plugin_id, 'inmail_counting');
      $this->assertEqual($this->user->sendstate->configuration['count'], $count);
    }

    // Process another one and check that the user is now muted.
    $raw = $this->getMessageFileContents('full.eml');
    $processor->process($raw, DelivererConfig::create(array('id' => 'test')));
    $this->user = User::load($this->user->id());
    $this->assertEqual($this->user->sendstate->plugin_id, 'inmail_temporarily_unreachable');
  }

  /**
   * Returns the content of a test message.
   *
   * @param string $filename
   *   The name of the file.
   *
   * @return string
   *   The contents of the file.
   */
  public function getMessageFileContents($filename) {
    $path = drupal_get_path('module', 'inmail_test') . '/eml/' . $filename;
    return file_get_contents(DRUPAL_ROOT . '/' . $path);
  }

  /**
   * Creates a new test user, deleting the previous one if it exists.
   *
   * The email address of the test user corresponds with the contents of the
   * test message files.
   */
  public function resetUser() {
    // Delete the user if it exists.
    if (isset($this->user)) {
      $this->user->delete();
    }
    // Create new user.
    $this->user = User::create(array(
      'name' => 'user',
      'mail' => 'user@example.org',
    ));
    $this->user->save();
  }

}
