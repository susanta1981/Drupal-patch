<?php

namespace Drupal\inmail;

use Drupal\inmail\Entity\DelivererConfig;

/**
 * The processor result collects outcomes of a single mail processing pass.
 *
 * @ingroup processing
 */
interface ProcessorResultInterface {

  /**
   * Set the deliverer of the message to which this result applies.
   *
   * @param \Drupal\inmail\Entity\DelivererConfig $deliverer
   *   The deliverer config entity.
   */
  public function setDeliverer(DelivererConfig $deliverer);

  /**
   * Get the deliverer of the message to which this result applies.
   *
   * @return \Drupal\inmail\Entity\DelivererConfig
   *   The deliverer config entity.
   */
  public function getDeliverer();

  /**
   * Returns an analyzer result instance, after first creating it if needed.
   *
   * If a result object has already been created with the given topic name, that
   * object will be used.
   *
   * @param string $topic
   *   An identifier for the analyzer result object.
   * @param callable $factory
   *   A function that returns an analyzer result object. This will be called if
   *   there is no object previously created for the given topic name.
   *
   * @return \Drupal\inmail\AnalyzerResultInterface
   *   The analyzer result object.
   *
   * @throws \InvalidArgumentException
   *   If the callable returns something else than an analyzer result object.
   */
  public function ensureAnalyzerResult($topic, callable $factory);

  /**
   * Returns an analyzer result instance.
   *
   * @param string $topic
   *   The identifier for the analyzer result object.
   *
   * @return \Drupal\inmail\AnalyzerResultInterface
   *   The analyzer result object. If no result object has yet been added for
   *   the given key, this returns NULL.
   */
  public function getAnalyzerResult($topic);

  /**
   * Returns all analyzer results.
   *
   * @return \Drupal\inmail\AnalyzerResultInterface[]
   *   A list of analyzer results.
   */
  public function getAnalyzerResults();

  /**
   * Add a log message to the processing logger.
   *
   * @param string $source
   *   The name of the analyzer or handler that produced the message.
   * @param string $message
   *   The log message.
   * @param array $placeholders
   *   Placeholder substitution map.
   */
  public function log($source, $message, array $placeholders = array());

  /**
   * Returns the log messages.
   *
   * This method must not be used by analyzers nor handlers. To make handlers
   * dependent on analyzer result types, use a dedicated class that implements
   * \Drupal\inmail\AnalyzerResultInterface.
   *
   * @return array
   *   A list of log items, each an associative array containing:
   *     - message: The log message.
   *     - placeholders: Placeholder substitution map.
   */
  public function readLog();

}
