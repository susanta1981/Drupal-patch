<?php

namespace Drupal\inmail\Plugin\inmail\Deliverer;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * A Fetcher is a Deliverer that can be executed.
 *
 * @todo Create Monitoring sensor for remaining count, https://www.drupal.org/node/2399779
 *
 * @ingroup deliverer
 */
interface FetcherInterface extends DelivererInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Connects to the configured mailbox and fetches new mail.
   *
   * @return string[]
   *   The fetched messages, in complete raw form.
   */
  public function fetch();

  /**
   * Returns the number of remaining messages to fetch.
   *
   * @return int|null
   *   Number of remaining messages, or NULL if it is unknown.
   */
  public function getCount();

  /**
   * Updates the remaining messages count.
   *
   * This may connect to a remote mail server.
   */
  public function update();

  /**
   * Update the timestamp of the last status check made.
   *
   * @param int|null $timestamp
   *   The Unix timestamp of the last update. Use NULL to specify that the
   *   status has never been checked.
   */
  public function setLastCheckedTime($timestamp);

  /**
   * Returns the timestamp for the last status check made.
   *
   * @return int
   *   The Unix timestamp of the last update.
   */
  public function getLastCheckedTime();

}
