<?php

namespace Drupal\inmail;

use Drupal\inmail\Entity\DelivererConfig;

/**
 * Provides methods to process an incoming message.
 *
 * @ingroup processing
 */
interface MessageProcessorInterface {

  /**
   * Analyzes an incoming message and executes callbacks as appropriate.
   *
   * In the iconical case, the message indicates a failed delivery of an earlier
   * outgoing message to a receiver, and a callback sets the receiver's send
   * state to mute.
   *
   * @param string $raw
   *   A raw mail message.
   * @param \Drupal\inmail\Entity\DelivererConfig $deliverer
   *   The Deliverer configuration that delivered the messages.
   */
  public function process($raw, DelivererConfig $deliverer);

  /**
   * Analyzes and executes callbacks for multiple messages.
   *
   * @todo consider use of array keys and deal with return status.
   *
   * @param string[] $messages
   *   A list of raw mail messages.
   * @param \Drupal\inmail\Entity\DelivererConfig $deliverer
   *   The Deliverer configuration that delivered the messages.
   *
   * @see MessageProcessorInterface::process()
   */
  public function processMultiple(array $messages, DelivererConfig $deliverer);

}
