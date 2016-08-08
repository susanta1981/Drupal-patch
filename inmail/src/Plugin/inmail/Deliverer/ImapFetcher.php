<?php

namespace Drupal\inmail\Plugin\inmail\Deliverer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches messages over IMAP.
 *
 * @ingroup deliverer
 *
 * @Deliverer(
 *   id = "imap",
 *   label = @Translation("IMAP")
 * )
 */
class ImapFetcher extends FetcherBase implements ContainerFactoryPluginInterface {

  /**
   * Injected Inmail logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * Injected site state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger_channel, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->loggerChannel = $logger_channel;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('inmail'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    return $this->doImap(function($imap_stream) {
      // Find IDs of unread messages.
      // @todo Introduce options for message selection, https://www.drupal.org/node/2405767
      $unread_ids = imap_search($imap_stream, 'UNSEEN') ?: array();
      $batch_ids = array_splice($unread_ids, 0, $this->configuration['batch_size']);

      // Get the header + body of each message.
      $raws = array();
      foreach ($batch_ids as $unread_id) {
        $raws[] = imap_fetchheader($imap_stream, $unread_id) . imap_body($imap_stream, $unread_id);
      }

      // Save number of unread messages.
      $this->setCount(count($unread_ids));
      $this->setLastCheckedTime(REQUEST_TIME);

      return $raws;
    }) ?: array();
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    $this->doImap(function($imap_stream) {
      $unread_ids = imap_search($imap_stream, 'UNSEEN') ?: array();
      $this->setCount(count($unread_ids));
      $this->setLastCheckedTime(REQUEST_TIME);
    });
  }

  /**
   * Connect to IMAP server and perform arbitrary operations.
   *
   * If connection fails, an exception is thrown and the callback is never
   * invoked.
   *
   * @param callable $callback
   *   A callable that takes an IMAP stream as argument.
   *
   * @return mixed
   *   The return value of the callback.
   *
   * @throws \Exception
   *   If connection fails.
   */
  protected function doImap(callable $callback) {
    // Connect to IMAP with details from configuration.
    $mailbox_flags = $this->configuration['ssl'] ? '/ssl' : '';
    $mailbox = '{' . $this->configuration['host'] . ':' . $this->configuration['port'] . $mailbox_flags . '}';
    $imap_res = imap_open($mailbox, $this->configuration['username'], $this->configuration['password']);

    if (empty($imap_res)) {
      // @todo Return noisily if misconfigured or imap missing. Possibly stop retrying, https://www.drupal.org/node/2405757
      $this->loggerChannel->error('Deliverer connection failed: @error', ['@error' => implode("\n", imap_errors())]);
      return NULL;
    }

    // Call callback.
    $return = $callback($imap_res);

    // Close connection.
    imap_close($imap_res);
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'host' => '',
      // Standard non-SSL IMAP port as defined by RFC 3501.
      'port' => 143,
      'ssl' => FALSE,
      'username' => '',
      'password' => '',
      'batch_size' => '100',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['account'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Account'),
    );
    $form['account']['info'] = array(
      '#type' => 'item',
      '#markup' => $this->t('Please refer to your email provider for the appropriate values for these fields.'),
    );
    $form['account']['host'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#default_value' => $this->configuration['host'],
    );

    $form['account']['port'] = array(
      '#type' => 'number',
      '#title' => $this->t('Port'),
      '#default_value' => $this->configuration['port'],
      '#description' => $this->t('The standard port number is 143, or 993 when using SSL.'),
    );

    $form['account']['ssl'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use SSL'),
      '#default_value' => $this->configuration['ssl'],
    );

    $form['account']['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->configuration['username'],
    );

    // Password field cannot have #default_value. To avoid forcing user to
    // re-enter password with each save, password updating is conditional on
    // this checkbox.
    $form['account']['password_update'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Update password'),
    );

    $form['account']['password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#states' => array(
        'visible' => array(
          ':input[name=password_update]' => array('checked' => TRUE),
        ),
      ),
    );

    // Always show password field if configuration is new.
    if ($form_state->getFormObject()->getEntity()->isNew()) {
      $form['account']['password_update']['#access'] = FALSE;
      $form['account']['password']['#states']['visible'] = array();
    }

    $form['batch_size'] = array(
      '#type' => 'number',
      '#title' => $this->t('Batch size'),
      '#default_value' => $this->configuration['batch_size'],
      '#description' => $this->t('How many messages to fetch on each invocation.'),
    );

    // Add a "Test connection" button.
    $form['account'] += parent::addTestConnectionButton();

    return $form;
  }

  /**
   * Updates the fetcher configuration.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function updateConfiguration(FormStateInterface $form_state) {
    $configuration = array(
      'host' => $form_state->getValue('host'),
      'port' => $form_state->getValue('port'),
      'ssl' => $form_state->getValue('ssl'),
      'username' => $form_state->getValue('username'),
      'batch_size' => $form_state->getValue('batch_size'),
    ) + $this->getConfiguration();

    // Only update password if "Update password" is checked.
    if ($form_state->getValue('password_update')) {
      $configuration['password'] = $form_state->getValue('password');
    }

    $this->setConfiguration($configuration);
  }

  /**
   * Checks the account credentials.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if credentials are valid. Otherwise, FALSE.
   */
  protected function hasValidCredentials(FormStateInterface $form_state) {
    $this->updateConfiguration($form_state);
    try {
      $hasValidCredentials = $this->doImap(function ($imap_stream) {
        // At this point IMAP connection is open and credentials are valid.
        return TRUE;
      });
    }
    catch (\Exception $e) {
      $hasValidCredentials = FALSE;
    }

    return (bool) $hasValidCredentials;
  }

  /**
   * Handles submit call of "Test connection" button.
   */
  public function submitTestConnection(array $form, FormStateInterface $form_state) {
    if ($this->hasValidCredentials($form_state)) {
      drupal_set_message(t('Valid credentials!'));
    }
    else {
      drupal_set_message(t('Invalid credentials!'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->updateConfiguration($form_state);
  }

}
