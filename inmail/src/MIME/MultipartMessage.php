<?php

namespace Drupal\inmail\MIME;

use Drupal\Component\Datetime\DateTimePlus;

/**
 * A multipart message.
 *
 * This is the combination of \Drupal\collect\MIME\MultipartEntity and
 * \Drupal\collect\MIME\Message.
 */
class MultipartMessage extends MultipartEntity implements MessageInterface {

  /**
   * {@inheritdoc}
   */
  public function getMessageId() {
    return $this->getHeader()->getFieldBody('Message-Id');
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->getHeader()->getFieldBody('Subject');
  }

  /**
   * {@inheritdoc}
   */
  public function getTo() {
    return $this->getHeader()->getFieldBody('To');
  }

  /**
   * {@inheritdoc}
   */
  public function getFrom() {
    return $this->getHeader()->getFieldBody('From');
  }

  /**
   * {@inheritdoc}
   */
  public function getReceivedDate() {
    // A message has one or more Received header fields. The first occurring is
    // the latest added. Its body has two parts separated by ';', the second
    // part being a date.
    $received_body = $this->getHeader()->getFieldBody('Received');
    list($info, $date_string) = explode(';', $received_body, 2);
    return new DateTimePlus($date_string);
  }

}
