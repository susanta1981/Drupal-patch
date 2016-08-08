<?php

namespace Drupal\inmail\Entity;

/**
 * Message handler configuration entity.
 *
 * This entity type is for storing the configuration of a handler plugin.
 *
 * @ingroup handler
 *
 * @ConfigEntityType(
 *   id = "inmail_handler",
 *   label = @Translation("Message handler"),
 *   admin_permission = "administer inmail",
 *   config_prefix = "handler",
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\inmail\Form\HandlerConfigurationForm"
 *     },
 *     "list_builder" = "Drupal\inmail\HandlerListBuilder"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "configuration",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/inmail/analyzers/{inmail_handler}",
 *     "enable" = "/admin/config/system/inmail/analyzers/{inmail_handler}/enable",
 *     "disable" = "/admin/config/system/inmail/analyzers/{inmail_handler}/disable"
 *   }
 * )
 */
class HandlerConfig extends PluginConfigEntity {
  // @todo Implement HandlerConfig::calculateDependencies() https://www.drupal.org/node/2379929
}
