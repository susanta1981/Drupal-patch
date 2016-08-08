<?php

namespace Drupal\inmail;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\inmail\Plugin\inmail\Deliverer\FetcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for message deliverer configurations.
 *
 * @ingroup deliverer
 */
class DelivererListBuilder extends ConfigEntityListBuilder {

  /**
   * The mail deliverer plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $delivererManager;

  /**
   * The injected date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new DelivererListBuilder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, PluginManagerInterface $deliverer_manager, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->delivererManager = $deliverer_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.inmail.deliverer'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['label'] = $this->t('Deliverer');
    $row['plugin'] = $this->t('Plugin');
    $row['count'] = $this->t('Unprocessed');
    $row['last_checked'] = $this->t('Last checked');
    return $row + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Set default values for each column.
    $row['label'] = $this->getLabel($entity);
    // @todo Replace with calculateDependencies(), https://www.drupal.org/node/2379929
    $row['plugin'] = $this->t('Plugin missing');
    $row['count'] = NULL;
    $row['last_checked'] = NULL;

    // Conditionally override values for some columns.
    /** @var \Drupal\inmail\Entity\DelivererConfig $entity */
    $plugin_id = $entity->getPluginId();
    if ($this->delivererManager->hasDefinition($plugin_id)) {
      $row['plugin'] = $this->delivererManager->getDefinition($plugin_id)['label'];
      $plugin = $this->delivererManager->createInstance($plugin_id, $entity->getConfiguration());

      if ($plugin instanceof FetcherInterface) {
        // Set the "Remaining messages" count.
        $count = $plugin->getCount();
        $row['count'] = isset($count) ? $count : $this->t('Unknown');

        // Set the relative time of last check.
        if ($last_checked = $plugin->getLastCheckedTime()) {
          $last_checked_formatted = $this->dateFormatter->formatInterval(REQUEST_TIME - $last_checked);
          $row['last_checked'] = $this->t('@time ago', ['@time' => $last_checked_formatted]);
        }
      }
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['edit']['title'] = $this->t('Configure');
    return $operations;
  }
}
