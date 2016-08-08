<?php

namespace Drupal\inmail\Plugin\inmail\Deliverer;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines methods for deliverers.
 *
 * Deliverers provide new messages from a specific source to Inmail.
 *
 * @ingroup deliverer
 */
interface DelivererInterface extends PluginInspectionInterface {

  /**
   * Returns the deliverer label.
   *
   * @return \Drupal\Core\StringTranslation\TranslationWrapper
   *   The deliverer label.
   */
  public function getLabel();

}
