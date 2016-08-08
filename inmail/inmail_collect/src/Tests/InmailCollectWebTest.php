<?php

namespace Drupal\inmail_collect\Tests;

use Drupal\inmail\Entity\DelivererConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the presentation of collected messages.
 *
 * @group inmail
 */
class InmailCollectWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('inmail_collect', 'block');

  /**
   * Tests the user interface.
   *
   * @see Drupal\inmail_collect\Plugin\collect\Model\InmailMessage::build()
   */
  public function testUi() {
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
    // Process and store a message.
    /** @var \Drupal\inmail\MessageProcessor $processor */
    $processor = \Drupal::service('inmail.processor');
    $raw = file_get_contents(\Drupal::root() . '/' . drupal_get_path('module', 'inmail_test') . '/eml/nouser.eml');
    $processor->process($raw, DelivererConfig::create(array('id' => 'test')));

    // Log in and view the list.
    $user = $this->drupalCreateUser(array('administer collect'));
    $this->drupalLogin($user);
    $this->drupalGet('admin/content/collect');
    $this->assertText('https://www.drupal.org/project/inmail/schema/message');
    $this->assertText(format_date(strtotime('19 Feb 2014 10:05:15 +0100'), 'short'));
    $origin_uri = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBasePath() . '/inmail/message/message-id/21386_1392800717_530473CD_21386_78_1_OF72A6C464.8DF6E397-ONC1257C84.0031EBBB-C1257C84.0031=EC2C+@acacia.example.org';
    $this->assertText($origin_uri);
    $this->assertText('application/json');

    // View details as JSON.
    $this->clickLink('View');
    $container_url = $this->getUrl();
    $this->assertText($origin_uri);
    $this->assertText(t('There is no plugin configured to display data.'));
    $this->clickLink(t('Raw data'));
    $this->assertText('&quot;header-subject&quot;: &quot;DELIVERY FAILURE: User environment (user@example.org) not listed in Domino Directory&quot;');
    $this->assertText('&quot;header-to&quot;: &quot;bounces+user=example.org@example.com&quot;');
    $this->assertText('&quot;header-from&quot;: &quot;Postmaster@acacia.example.org&quot;');
    // '<' and '>' are converted to /u003C and /u003E entities by the formatter.
    $this->assertText('&quot;header-message-id&quot;: &quot;\u003C21386_1392800717_530473CD_21386_78_1_OF72A6C464.8DF6E397-ONC1257C84.0031EBBB-C1257C84.0031=EC2C+@acacia.example.org\u003E&quot;');
    $this->assertText('&quot;deliverer&quot;: &quot;test&quot;');
    // Last line of the raw message.
    $this->assertText('--==IFJRGLKFGIR25201654UHRUHIHD--');

    // Create suggested Inmail model and view details as rendered.
    $this->drupalGet($container_url);
    $this->clickLink(t('Set up a @label model', ['@label' => 'Email message']));
    $this->drupalPostForm(NULL, array('id' => 'email_message'), t('Save'));
    // Details summaries of each part.
    $details= $this->xpath('//div[@class="field__item"]//details');
    $this->assertEqual((string) $details[0]->summary, 'DELIVERY FAILURE: User environment (user@example.org) not listed in Domino Directory');
    $this->assertEqual((string) $details[0]->div->details[0]->summary, t('Part 1'));
    $this->assertEqual((string) $details[0]->div->details[1]->summary, t('Part 2'));
    $this->assertEqual((string) $details[0]->div->details[2]->summary, t('Part 3'));
    // Eliminate repeated whitespace to simplify matching.
    $this->setRawContent(preg_replace('/\s+/', ' ', $this->getRawContent()));
    // Header fields.
    $this->assertText(t('From') . ' Postmaster@acacia.example.org');
    $this->assertText(t('To') . ' bounces+user=example.org@example.com');
    $this->assertText(t('Subject') . ' DELIVERY FAILURE: User environment (user@example.org) not listed in Domino Directory');
    $this->assertText(t('Content-Type') . ' multipart/report');
    $this->assertText(t('Content-Type') . ' text/plain');
    $this->assertText(t('Content-Type') . ' message/delivery-status');
    $this->assertText(t('Content-Type') . ' message/rfc822');
    // Body.
    $this->assertText('Your message Subject: We want a toxic-free future was not delivered to: environment@lvmh.fr');
  }

}
