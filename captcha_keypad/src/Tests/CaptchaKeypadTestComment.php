<?php

namespace Drupal\captcha_keypad\Tests;

use Drupal\simpletest\WebTestBase;
/**
 * Tests Captcha Keypad on comment forms.
 *
 * @group captcha_keypad
 */
class CaptchaKeypadTestComment extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_ui', 'comment', 'captcha_keypad');

  /**
   * A user with the 'Administer Captcha keypad' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser(array('administer captcha keypad'), 'Captcha Keypad Admin', TRUE);
  }

  /**
   * Test for Contact forms.
   */
  function testCaptchaKeypadCommentForm() {
    $this->drupalLogin($this->adminUser);

    // Create article node type.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    // Create comment and attach to content type.

    // Enable Captcha keypad on contact form.
    $this->drupalGet('admin/config/system/captcha_keypad');

    // Create content.

    // Test Captcha Keypad.
  }
}
