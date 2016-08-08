<?php

namespace Drupal\inmail\Tests;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\Entity\HandlerConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the UI of Inmail.
 *
 * @group inmail
 */
class InmailWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('inmail', 'inmail_test', 'block');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the admin UI.
   */
  public function testAdminUi() {
    // Create a test user and log in.
    $user = $this->drupalCreateUser(array(
      'access administration pages',
      'administer inmail',
    ));
    $this->drupalLogin($user);

    // Check the form.
    $this->drupalGet('admin/config');
    $this->clickLink('Inmail');
    $this->assertField('return_path');

    // Check validation.
    $this->drupalPostForm(NULL, ['return_path' => 'not an address'], 'Save configuration');
    $this->assertText('The email address not an address is not valid.');

    $this->drupalPostForm(NULL, ['return_path' => 'not+allowed@example.com'], 'Save configuration');
    $this->assertText('The address may not contain a + character.');

    $this->drupalPostForm(NULL, ['return_path' => 'bounces@example.com'], 'Save configuration');
    $this->assertText('The configuration options have been saved.');

    $this->drupalPostForm(NULL, ['return_path' => ''], 'Save configuration');
    $this->assertText('The configuration options have been saved.');

    // Check other parts of UI. Saving some time by not implementing them as
    // proper test methods.
    $this->doTestDelivererUi();
    $this->doTestAnalyzerUi();
    $this->doTestHandlerUi();
  }

  /**
   * Tests the listing and configuration form of deliverers.
   *
   * @see \Drupal\inmail\DelivererListBuilder
   * @see \Drupal\inmail\Form\DelivererConfigurationForm
   * @see \Drupal\inmail\Plugin\inmail\Fetcher\ImapFetcher
   */
  protected function doTestDelivererUi() {
    // Check Deliverer list.
    $this->clickLink('Mail deliverers');
    $this->assertUrl('admin/config/system/inmail/deliverers');
    $this->assertText('There is no Mail deliverer yet.');

    // Add an IMAP fetcher.
    $this->clickLink('Add deliverer');
    $this->assertUrl('admin/config/system/inmail/deliverers/add');
    // Select the IMAP plugin.
    $edit = array(
      'label' => 'Test IMAP Fetcher',
      'id' => 'test_imap',
      'plugin' => 'imap',
    );
    $this->drupalPostAjaxForm(NULL, $edit, 'plugin');
    $this->assertText('Account');
    $this->assertRaw('<input data-drupal-selector="edit-test-connection" type="submit"');
    $edit += array(
      'host' => 'imap.example.com',
      'username' => 'user',
      'password' => 'pass',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertUrl('admin/config/system/inmail/deliverers');
    $this->assertText('Test IMAP Fetcher');

    // Add a Drush deliverer. It implements different interfaces and
    // PluginConfigurationForm has to support that.
    $this->drupalGet('admin/config/system/inmail/deliverers/add');
    $edit = array(
      'label' => 'Test Drush Deliverer',
      'id' => 'test_drush',
      'plugin' => 'drush',
    );
    $this->drupalPostAjaxForm(NULL, $edit, 'plugin');
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Test Drush Deliverer');

    // Operation links should be present.
    $this->clickLink('Disable');
    $this->clickLink('Enable');
    $this->assertLink('Delete');
    $this->clickLink('Configure');
    $this->assertUrl('admin/config/system/inmail/deliverers/test_drush');

    // Test 'Delete' link.
    $this->clickLink('Delete');
    $this->assertText('Are you sure you want to delete the deliverer configuration Test Drush Deliverer?');
    $this->drupalPostForm(NULL, array(), 'Delete');
    $this->assertText('The Test Drush Deliverer deliverer has been deleted.');
    $this->assertUrl('admin/config/system/inmail/deliverers');
    // Drush item should be removed. Cannot check for config label because it is
    // in the removal message, instead check for plugin label.
    $this->assertNoText('Drush inmail-process');
    // Also test deleting fetcher, may be significant because its interface is
    // different.
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, array(), 'Delete');
    $this->assertText('There is no Mail deliverer yet.');

    // Add test fetcher.
    $edit = array(
      'label' => 'Test Test Fetcher',
      'id' => 'test_test',
      'plugin' => 'test_fetcher',
    );
    $this->drupalPostAjaxForm('admin/config/system/inmail/deliverers/add', $edit, 'plugin');
    $this->drupalPostForm(NULL, $edit, 'Save');
    $overview_count_xpath = '//td[text()="100"]';
    $this->assertNoFieldByXPath($overview_count_xpath);
    $this->drupalPostForm(NULL, array(), 'Check fetcher status');
    $this->assertText('Fetcher state info has been updated.');
    $this->assertFieldByXPath($overview_count_xpath);
  }

  /**
   * Tests the listing and configuration form of analyzers.
   *
   * @see \Drupal\inmail\AnalyzerListBuilder
   * @see \Drupal\inmail\Form\AnalyzerConfigurationForm
   * @see \Drupal\inmail\Plugin\inmail\Analyzer\StandardDSNAnalyzer
   * @see \Drupal\inmail\Plugin\inmail\Analyzer\StandardDSNReasonAnalyzer
   * @see \Drupal\inmail\Plugin\inmail\Analyzer\VerpAnalyzer
   */
  protected function doTestAnalyzerUi() {
    // Check Analyzer list.
    $this->clickLink('Message analyzers');
    $this->assertUrl('admin/config/system/inmail/analyzers');
    $this->assertText('Standard DSN Analyzer');
    $this->assertText('Standard bounce analyzer');
    $this->assertText('Standard DSN Reason Analyzer');
    $this->assertText('Bounce reason message');
    $this->assertText('VERP Analyzer');
    $this->assertText('VERP address verification');

    // Status operations should be present.
    $this->assertNoLink('Enable');
    $this->clickLink('Disable');
    $this->clickLink('Enable');

    // Check default value of the status field when configuring.
    $this->clickLink('Configure');
    $this->assertFieldChecked('edit-status');
    $this->drupalPostForm(NULL, ['status' => FALSE], 'Save');
    $this->clickLink('Configure');
    $this->assertNoFieldChecked('edit-status');
    $this->drupalPostForm(NULL, ['status' => TRUE], 'Save');

    // The analyzers should be ordered according to default config.
    $this->assertFieldByXPath('//table[@id="edit-entities"]/tbody/tr[1]/td/text()', 'VERP Analyzer');
    $this->assertFieldByXPath('//table[@id="edit-entities"]/tbody/tr[2]/td/text()', 'Standard DSN Analyzer');
    $this->assertFieldByXPath('//table[@id="edit-entities"]/tbody/tr[3]/td/text()', 'Standard DSN Reason Analyzer');

    // Configs referring to missing plugins should not cause errors, but show a
    // message.
    AnalyzerConfig::create(array(
      'id' => 'unicorn',
      'plugin_id' => 'unicorn',
      'label' => 'Unicorn',
    ))->save();
    $this->drupalGet('admin/config/system/inmail/analyzers');
    $this->assertText('Unicorn');
    // @todo Improve style for "broken" plugin https://www.drupal.org/node/2379777
    $this->assertText('Plugin missing');
  }

  /**
   * Tests the listing and configuration form of handlers.
   *
   * @see \Drupal\inmail\HandlerListBuilder
   * @see \Drupal\inmail\Form\HandlerConfigurationForm
   * @see \Drupal\inmail\Plugin\inmail\Handler\ModeratorForwardHandler
   */
  protected function doTestHandlerUi() {
    // Check Handler list and fallback plugin.
    $this->clickLink('Message handlers');
    $this->assertUrl('admin/config/system/inmail/handlers');
    $this->assertText('Forward unclassified bounces');

    // Status operations should be present.
    $this->assertNoLink('Enable');
    $this->clickLink('Disable');
    $this->assertNoLink('Disable');
    $this->assertLink('Enable');

    // Configs referring to missing plugins should not cause errors, but show a
    // message.
    HandlerConfig::create(array(
      'id' => 'unicorn',
      'plugin_id' => 'unicorn',
      'label' => 'Unicorn',
    ))->save();
    $this->drupalGet('admin/config/system/inmail/handlers');
    $this->assertText('Unicorn');
    // @todo Improve style for "broken" plugin https://www.drupal.org/node/2379777
    $this->assertText('Plugin missing');

    // Configure a handler.
    $this->clickLink('Configure');
    $this->assertUrl('admin/config/system/inmail/handlers/moderator_forward');
    $this->drupalPostForm(NULL, ['moderator' => 'moderator@example.com'], 'Save');
    $this->assertUrl('admin/config/system/inmail/handlers');
    $this->clickLink('Configure');
    $this->assertFieldByName('moderator', 'moderator@example.com');
  }

}
