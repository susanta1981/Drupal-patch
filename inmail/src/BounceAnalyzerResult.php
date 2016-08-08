<?php

namespace Drupal\inmail;

/**
 * Contains analyzer results.
 *
 * The setter methods only have effect the first time they are called, so values
 * are only writable once.
 *
 * @ingroup analyzer
 */
class BounceAnalyzerResult implements AnalyzerResultInterface {

  /**
   * Identifies this class in relation to other analyzer results.
   *
   * Use this as the $topic argument for ProcessorResultInterface methods.
   *
   * @see \Drupal\inmail\ProcessorResultInterface
   */
  const TOPIC = 'bounce';

  /**
   * The reported status code.
   *
   * @var \Drupal\inmail\DSNStatus
   */
  protected $statusCode;

  /**
   * The reported recipient.
   *
   * @var string
   */
  protected $recipient;

  /**
   * The reported bounce reason.
   *
   * @var string
   */
  protected $reason;

  /**
   * Returns a function closure that in turn returns a new class instance.
   *
   * @return callable
   *   A factory closure that returns a new BounceAnalyzerResult object when
   *   called.
   */
  public static function createFactory() {
    return function() {
      return new static();
    };
  }

  /**
   * Report the intended recipient for a bounce message.
   *
   * @param string $recipient
   *   The address of the recipient.
   */
  public function setRecipient($recipient) {
    if (!isset($this->recipient)) {
      $this->recipient = $recipient;
    }
  }

  /**
   * Report the status code of a bounce message.
   *
   * @param \Drupal\inmail\DSNStatus $code
   *   A status code.
   */
  public function setStatusCode(DSNStatus $code) {
    if (!isset($this->statusCode)) {
      $this->statusCode = $code;
      return;
    }

    // If subject and detail are 0 (like X.0.0), allow overriding those.
    $current_code = $this->statusCode;
    if ($current_code->getSubject() == 0 && $current_code->getDetail() == 0) {
      $new_code = new DSNStatus($current_code->getClass(), $code->getSubject(), $code->getDetail());
      $this->statusCode = $new_code;
    }
  }

  /**
   * Report the reason for a bounce message.
   *
   * @param string $reason
   *   Human-readable information in English explaning why the bounce happened.
   */
  public function setReason($reason) {
    if (!isset($this->reason)) {
      $this->reason = $reason;
    }
  }

  /**
   * Returns the reported recipient for a bounce message.
   *
   * @return string|null
   *   The address of the intended recipient, or NULL if it has not been
   *   reported.
   */
  public function getRecipient() {
    return $this->recipient;
  }

  /**
   * Returns the reported status code of a bounce message.
   *
   * @return \Drupal\inmail\DSNStatus
   *   The status code, or NULL if it has not been reported.
   */
  public function getStatusCode() {
    return $this->statusCode;
  }

  /**
   * Returns the reason for a bounce message.
   *
   * @return string|null
   *   The reason message, in English, or NULL if it has not been reported.
   */
  public function getReason() {
    return $this->reason;
  }

  /**
   * Tells whether any analyzer has classified the message as a bounce.
   *
   * @return bool
   *   TRUE if a bounce analyzer has reported a recipient address and a status
   *   code like 4.X.X or 5.X.X, otherwise FALSE.
   */
  public function isBounce() {
    $recipient = $this->getRecipient();
    if (empty($recipient)) {
      return FALSE;
    }

    $status_code = $this->getStatusCode();
    // If there is a status code, it almost certainly indicates a failure
    // (there's no reason to generate a DSN when delivery is successful), but
    // let's check isSuccess() for the sake of correctness.
    if (empty($status_code) || $status_code->isSuccess()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function summarize() {
    $summary = array();
    if ($this->getRecipient()) {
      $summary['recipient'] = $this->getRecipient();
    }
    if ($this->getStatusCode() && $this->getStatusCode()->getCode()) {
      $summary['code'] = $this->getStatusCode()->getCode();
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return t('Bounce');
  }
}
