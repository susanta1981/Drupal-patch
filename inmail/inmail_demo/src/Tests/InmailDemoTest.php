<?php

namespace Drupal\inmail_demo\Tests;

use Drupal\inmail\Entity\HandlerConfig;
use Drupal\inmail_test\Plugin\inmail\Handler\ResultKeeperHandler;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the demo module for Inmail.
 *
 * @group inmail
 */
class InmailDemoTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array('inmail_demo', 'inmail_test');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    HandlerConfig::create(array('id' => 'result_keeper', 'plugin' => 'result_keeper'))->save();
  }

  /**
   * Tests the paste form.
   */
  protected function testPasteForm() {
    $this->drupalGet('admin/config/system/inmail/paste');
    $this->assertResponse(403);
    $this->drupalLogin($this->drupalCreateUser(['administer inmail']));
    $this->drupalGet('admin/config/system/inmail/paste');
    $this->assertFieldByName('deliverer', 'paste');
    $this->drupalPostAjaxForm(NULL, [], ['op' => t('Load example')]);
    $this->drupalPostForm(NULL, [], t('Process email'));
    $this->assertEqual('Re: Hello', ResultKeeperHandler::getMessage()->getSubject());
    $this->assertEqual('paste', ResultKeeperHandler::getResult()->getDeliverer()->id());
  }

}
