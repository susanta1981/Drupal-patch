<?php

namespace Drupal\inmail;

use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\user\UserInterface;

/**
 * Contains default analyzer result.
 *
 * @ingroup analyzer
 */
class DefaultAnalyzerResult implements AnalyzerResultInterface, DefaultAnalyzerResultInterface {

  /**
   * Identifies this class in relation to other analyzer results.
   *
   * Use this as the $topic argument for ProcessorResultInterface methods.
   *
   * @see \Drupal\inmail\ProcessorResultInterface
   */
  const TOPIC = 'default';

  /**
   * An array of collected contexts for this analyzer result.
   *
   * It contains information provided by analyzers that are
   * not part of default properties.
   *
   * @var \Drupal\Core\Plugin\Context\ContextInterface[]
   */
  protected $contexts = [];

  /**
   * The sender.
   *
   * @var string
   */
  protected $sender;

  /**
   * The account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The analyzed body of the message.
   *
   * @var string
   */
  protected $body;

  /**
   * The message footer
   *
   * @var string
   */
  protected $footer;

  /**
   * The message subject.
   *
   * @var string
   */
  protected $subject;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return t('Default Result');
  }

  /**
   * Returns a function closure that in turn returns a new class instance.
   *
   * @return callable
   *   A factory closure that returns a new DefaultAnalyzerResult object
   *   when called.
   */
  public static function createFactory() {
    return function() {
      return new static();
    };
  }

  /**
   * {@inheritdoc}
   */
  public function setSender($sender) {
    $this->sender = $sender;
  }

  /**
   * {@inheritdoc}
   */
  public function getSender() {
    return $this->sender;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount(UserInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function isUserAuthenticated() {
    return $this->account ? $this->account->isAuthenticated() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summarize() {
    $summary = [];
    if ($this->getSender()) {
      $summary['sender'] = $this->getSender();
    }
    if ($this->getSubject()) {
      $summary['subject'] = $this->getSubject();
    }
    if ($this->getAllContexts()) {
      $summary['contexts'] = t('The result contains @contexts contexts.', ['@contexts' => implode(', ', array_keys($this->getAllContexts()))]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * {@inheritdoc}
   */
  public function setBody($body) {
    $this->body = $body;
  }

  /**
   * {@inheritdoc}
   */
  public function getFooter() {
    return $this->footer;
  }

  /**
   * {@inheritdoc}
   */
  public function setFooter($footer) {
    $this->footer = $footer;
  }

  /**
   * @inheritDoc
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * @inheritDoc
   */
  public function setSubject($subject) {
    $this->subject = $subject;
  }

  /**
   * @inheritDoc
   */
  public function setContext($name, ContextInterface $context) {
    $this->contexts[$name] = $context;
  }

  /**
   * @inheritDoc
   */
  public function getAllContexts() {
    return $this->contexts;
  }

  /**
   * @inheritDoc
   */
  public function hasContext($name) {
    return isset($this->contexts[$name]);
  }

  /**
   * @inheritDoc
   */
  public function getContext($name) {
    if (!isset($this->contexts[$name])) {
      throw new \InvalidArgumentException('Context "' . $name . '" does not exist.');
    }

    return $this->contexts[$name];
  }

  /**
   * @inheritDoc
   */
  public function getContextsWithType($type) {
    $filtered_contexts = [];

    foreach ($this->contexts as $context_name => $context) {
      if ($context->getContextDefinition()->getDataType() == $type) {
        $filtered_contexts[$context_name] = $context;
      }
    }

    return $filtered_contexts;
  }


}
