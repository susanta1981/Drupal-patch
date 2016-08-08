<?php

namespace Drupal\inmail\Plugin\inmail\Handler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\inmail\BounceAnalyzerResult;
use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\ProcessorResultInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Message handler that forwards unclassified bounces by email to a moderator.
 *
 * @todo Validate moderator email address https://www.drupal.org/node/2381855
 * @todo Test logging https://www.drupal.org/node/2381933
 *
 * @Handler(
 *   id = "moderator_forward",
 *   label = @Translation("Moderator Forward"),
 *   description = @Translation("Forwards non-bounces by email to a moderator.")
 * )
 */
class ModeratorForwardHandler extends HandlerBase implements ContainerFactoryPluginInterface {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail'),
      $container->get('config.factory')->get('system.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    return array(
      '#type' => 'item',
      '#markup' => $this->t('Messages are forwarded with minimal modification. The header <code>X-Inmail-Forwarded</code> is added, and the <code>To</code> is changed to match the moderator address. Note that the Mail Transfer Agent (MTA) may add a few more headers when sending the message.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function invoke(MessageInterface $message, ProcessorResultInterface $processor_result) {
    // Cancel if the moderator email is not set.
    if (!($moderator = $this->getModerator())) {
      $processor_result->log('ModeratorForwardHandler', 'Moderator email address not set');
      return;
    }

    /** @var \Drupal\inmail\BounceAnalyzerResult $result */
    $result = $processor_result->getAnalyzerResult(BounceAnalyzerResult::TOPIC);

    // Cancel if the message is successfully classified.
    if ($result->isBounce()) {
      return;
    }

    // Cancel and make noise if it was the moderator address that bounced!
    // This is for the off chance that we identified the intended recipient
    // but not a bounce status code.
    if ($result->getRecipient() == $moderator) {
      $processor_result->log('ModeratorForwardHandler', 'Moderator %address is bouncing.', array('%address' => $moderator));
      return;
    }

    // Cancel and make noise if this message rings a bell.
    if ($message->getHeader()->getFieldBody('X-Inmail-Forwarded')) {
      $processor_result->log('ModeratorForwardHandler', 'Refused to forward the same email twice (%subject).', array('%subject' => $message->getSubject()));
      return;
    }

    // Send forward.
    // DirectMail is set as mail plugin on install.
    // Message is composed in inmail_mail().
    $params = array('original' => $message);
    $this->mailManager->mail('inmail', 'handler_moderator_forward', $moderator, \Drupal::languageManager()->getDefaultLanguage(), $params);
  }

  /**
   * Returns the address that email is forwarded to.
   *
   * @return string
   *   Email address of moderator.
   */
  public function getModerator() {
    return $this->configuration['moderator'];
  }

  /**
   * Set the address that email should be forwarded to.
   *
   * @param string $moderator
   *   Email address of moderator.
   */
  public function setModerator($moderator) {
    $this->configuration['moderator'] = strval($moderator);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'moderator' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // @todo Validate email: https://www.drupal.org/node/2381855
    $form['moderator'] = array(
      '#type' => 'email',
      '#title' => $this->t('Moderator address'),
      '#description' => $this->t('Unclassified bounce messages are forwarded to this email address. <strong>Important:</strong> If using <em>Mailmute</em>, make sure this address does not belong to a user, since that will make the forward subject to that user\'s send state.'),
      '#description_position' => 'after',
      '#default_value' => $this->getModerator(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['moderator'] = $form_state->getValue('moderator');
  }

}
