<?php

namespace Drupal\inmail\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Plugin\inmail\Deliverer\FetcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wraps the deliverer list builder in a form, to enable interactive elements.
 *
 * @ingroup deliverer
 */
class DelivererListForm extends FormBase {

  /**
   * The injected deliverer plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $delivererManager;

  /**
   * Constructs a new DelivererListForm.
   */
  public function __construct(PluginManagerInterface $deliverer_manager) {
    $this->delivererManager = $deliverer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.inmail.deliverer'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inmail_deliverer_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    // Add update button.
    $form['check'] = array(
      '#type' => 'details',
      '#title' => $this->t('Operations'),
      '#open' => TRUE,
    );
    $form['check']['check_button'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Check fetcher status'),
    );

    // Let the list builder render the table.
    $form['table'] = \Drupal::entityManager()->getListBuilder('inmail_deliverer')->render();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update status of each fetcher.
    $fetchers_count = 0;
    foreach (DelivererConfig::loadMultiple() as $deliverer_config) {
      // Get plugin instance.
      $deliverer = $this->delivererManager->createInstance($deliverer_config->getPluginId(), $deliverer_config->getConfiguration());
      // Update plugin.
      if ($deliverer instanceof FetcherInterface) {
        $deliverer->update();
        $deliverer->setLastCheckedTime(REQUEST_TIME);
        $fetchers_count++;
      }
    }

    // Set a message and redirect to overview.
    if ($fetchers_count > 0) {
      drupal_set_message('Fetcher state info has been updated.');
    }
    else {
      drupal_set_message('There are no configured fetchers, nothing to update.');
    }
  }

}
