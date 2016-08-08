<?php

namespace Drupal\inmail\Entity;

/**
 * Message analyzer configuration entity.
 *
 * This entity type is for storing the configuration of an analyzer plugin.
 *
 * @ingroup analyzer
 *
 * @ConfigEntityType(
 *   id = "inmail_analyzer",
 *   label = @Translation("Message analyzer"),
 *   admin_permission = "administer inmail",
 *   config_prefix = "analyzer",
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\inmail\Form\AnalyzerConfigurationForm"
 *     },
 *     "list_builder" = "Drupal\inmail\AnalyzerListBuilder"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "configuration",
 *     "weight",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/inmail/analyzers/{inmail_analyzer}",
 *     "enable" = "/admin/config/system/inmail/analyzers/{inmail_analyzer}/enable",
 *     "disable" = "/admin/config/system/inmail/analyzers/{inmail_analyzer}/disable"
 *   }
 * )
 */
class AnalyzerConfig extends PluginConfigEntity {

  /**
   * The weight of the analyzer configuration.
   *
   * Analyzers with lower weights are invoked before those with higher weights.
   *
   * @var int
   */
  protected $weight;

}
