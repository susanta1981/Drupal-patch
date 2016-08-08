<?php

namespace Drupal\Tests\inmail\Kernel;

use Drupal\inmail\Entity\DelivererConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests mail deliverers.
 *
 * @group inmail
 */
class DelivererTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['inmail', 'inmail_test'];

  /**
   * Test that Cron runs trigger fetchers.
   *
   * @see inmail_cron()
   * @see \Drupal\inmail_test\Plugin\inmail\Deliverer\TestDeliverer
   */
  public function testCronInvocation() {
    // Setup fetcher.
    $deliverer_config = DelivererConfig::create(array(
      'id' => $this->randomMachineName(),
      'plugin' => 'test_fetcher',
    ));
    $deliverer_config->save();

    // Cron should trigger the fetcher.
    /** @var \Drupal\Core\CronInterface $cron */
    $cron = \Drupal::service('cron');
    $cron->run();
    $this->assertEqual(\Drupal::state()->get('inmail.test.deliver_count'), 1);

    // Disable deliverer and assert that it is not triggered.
    $deliverer_config->disable()->save();
    $cron->run();
    $this->assertEqual(\Drupal::state()->get('inmail.test.deliver_count'), 1);
  }

}
