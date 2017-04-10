<?php

namespace Drupal\captcha_keypad\Tests;

use Drupal\simpletest\WebTestBase;
/**
 * Tests Captcha Keypad on User forms.
 *
 * @group captcha_keypad
 */
class CaptchaKeypadTestUser extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contact', 'user', 'captcha_keypad');

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

  function testLinkToConfig() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/modules');
    $link = $this->xpath('//a[contains(@href, :href) and contains(@id, :id)]', [
      ':href' => 'admin/config/system/captcha_keypad',
      ':id' => 'edit-modules-spam-control-captcha-keypad-links-configure'
    ]);
    $this->assertTrue(count($link) === 1, 'Link to config is present');
  }

  function testUserForms() {
    $this->drupalLogin($this->adminUser);

    $edit = array();
    $edit['captcha_keypad_code_size'] = 99;
    $edit['captcha_keypad_shuffle_keypad'] = FALSE;
    $edit['captcha_keypad_forms[user_register_form]'] = 1;
    $edit['captcha_keypad_forms[user_login_form]'] = 1;
    $edit['captcha_keypad_forms[user_pass]'] = 1;
    $this->drupalPostForm('admin/config/system/captcha_keypad', $edit, t('Save configuration'));

    $this->drupalGet('admin/config/system/captcha_keypad');
    $element = $this->xpath('//input[@type="checkbox" and @name="captcha_keypad_shuffle_keypad" and @checked="checked"]');
    $this->assertTrue(count($element) === 0, 'Shuffle form is not checked.');

    $element = $this->xpath('//input[@type="text" and @id="edit-captcha-keypad-code-size" and @value="99"]');
    $this->assertTrue(count($element) === 1, 'The code size is correct.');

    $element = $this->xpath('//input[@type="checkbox" and @name="captcha_keypad_forms[user_register_form]" and @checked="checked"]');
    $this->assertTrue(count($element) === 1, 'Register form is checked.');

    $element = $this->xpath('//input[@type="checkbox" and @name="captcha_keypad_forms[user_login_form]" and @checked="checked"]');
    $this->assertTrue(count($element) === 1, 'User login form is checked.');

    $element = $this->xpath('//input[@type="checkbox" and @name="captcha_keypad_forms[user_pass]" and @checked="checked"]');
    $this->assertTrue(count($element) === 1, 'Forgot password form is checked.');

    $this->drupalLogout();

    // User password form.
    $this->drupalGet('/user/password');
    $element = $this->xpath('//input[@type="text" and @id="edit-captcha-keypad-input" and @value=""]');
    $this->assertTrue(count($element) === 1, 'The input text is present.');

    for ($i = 1; $i <= 9; $i++) {
      $element = $this->xpath('//span[@class="captcha-keypad"]/span/span[text()="' . $i . '"]');
      $this->assertTrue(count($element) === 1, 'Button ' . $i . ' is present.');
    }

    $this->assertText('Click/tap this sequence: testing');

    // User register form.
    $this->drupalGet('/user/register');
    $element = $this->xpath('//input[@type="text" and @id="edit-captcha-keypad-input" and @value=""]');
    $this->assertTrue(count($element) === 1, 'The input text is present.');

    // User login form.
    $edit = array();
    $edit['name'] = $this->adminUser->getAccountName();
    $edit['pass'] = $this->adminUser->getPassword();
    $this->drupalPostForm('user/login', $edit, t('Log in'));

    $element = $this->xpath('//input[@type="text" and @id="edit-captcha-keypad-input" and @value=""]');
    $this->assertTrue(count($element) === 1, 'The input text is present.');
  }

}
