<?php

namespace Drupal\inmail\Entity;

/**
 * Mail deliverer configuration entity.
 *
 * @ingroup deliverer
 *
 * @ConfigEntityType(
 *   id = "inmail_deliverer",
 *   label = @Translation("Mail deliverer"),
 *   admin_permission = "administer inmail",
 *   config_prefix = "deliverer",
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\inmail\Form\DelivererConfigurationForm",
 *       "add" = "Drupal\inmail\Form\DelivererConfigurationForm",
 *       "delete" = "Drupal\inmail\Form\DelivererDeleteForm"
 *     },
 *     "list_builder" = "Drupal\inmail\DelivererListBuilder"
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
 *     "edit-form" = "/admin/config/system/inmail/analyzers/{inmail_deliverer}",
 *     "delete-form" = "/admin/config/system/inmail/analyzers/{inmail_deliverer}/delete",
 *     "enable" = "/admin/config/system/inmail/analyzers/{inmail_deliverer}/enable",
 *     "disable" = "/admin/config/system/inmail/analyzers/{inmail_deliverer}/disable"
 *   }
 * )
 */
class DelivererConfig extends PluginConfigEntity {
}
