<?php

namespace Drupal\inmail\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines a config entity skeleton for plugin configuration.
 */
abstract class PluginConfigEntity extends ConfigEntityBase {

  /**
   * The machine name of the plugin configuration.
   *
   * @var string
   */
  protected $id;

  /**
   * The translatable, human-readable name of the plugin configuration.
   *
   * @var string
   */
  protected $label;

  /**
   * The ID of the plugin for this configuration.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The configuration for the plugin.
   *
   * @var array
   */
  protected $configuration = array();

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The machine name of this plugin.
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * Returns the configuration stored for this plugin.
   *
   * @return array
   *   The plugin configuration. Its properties are defined by the associated
   *   plugin.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Replaces the configuration stored for this plugin.
   *
   * @param array $configuration
   *   New plugin configuraion. Should match the properties defined by the
   *   plugin referenced by ::$plugin.
   *
   * @return $this
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
    return $this;
  }
}
