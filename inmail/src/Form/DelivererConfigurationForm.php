<?php

namespace Drupal\inmail\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for deliverers.
 *
 * Deliverer plugins that inherit
 * \Drupal\Component\Plugin\ConfigurablePluginInterface may specify
 * plugin-specific configuration.
 *
 * @ingroup deliverer
 */
class DelivererConfigurationForm extends PluginConfigurationForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.inmail.deliverer'),
      $container->get('entity.manager')->getStorage('inmail_deliverer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('entity.inmail_deliverer.collection');
  }

}
