<?php

namespace Drupal\captcha_keypad\Tests;

use Drupal\simpletest\WebTestBase;
/**
 * Tests Captcha Keypad on contact pages.
 *
 * @group captcha_keypad
 */
class CaptchaKeypadTestForum extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('forum', 'captcha_keypad');

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
  function testCaptchaKeypadForumForm() {
    $this->drupalLogin($this->adminUser);

    // Turn on Captcha keypad for the forum form.
    $edit = array();
    $edit['captcha_keypad_code_size'] = 99;
    $edit['captcha_keypad_forms[comment_comment_forum_form]'] = 1;
    $this->drupalPostForm('admin/config/system/captcha_keypad', $edit, t('Save configuration'));

    $this->drupalGet('admin/config/system/captcha_keypad');
    $element = $this->xpath('//input[@type="text" and @id="edit-captcha-keypad-code-size" and @value="99"]');
    $this->assertTrue(count($element) === 1, 'The code size is correct.');

    $element = $this->xpath('//input[@type="checkbox" and @name="captcha_keypad_forms[comment_comment_forum_form]" and @checked="checked"]');
    $this->assertTrue(count($element) === 1, 'Forum form is checked.');

    $this->drupalGet('forum');

    // Create new forum topic.
    $edit = array();
    $edit['title[0][value]'] = 'Foo';
    $this->drupalPostForm('node/add/forum', $edit, t('Save and publish'), array('query' => array('forum_id' => '1')));
    $this->assertText('Forum topic Foo has been created.');

    // Submit form without captcha code.
    $edit = array();
    $edit['comment_body[0][value]'] = 'Foo';
    $this->drupalPostForm($this->getUrl(), $edit, t('Save'));
    $this->assertText('Invalid security code.');

    // Submit form with captcha code.
    $edit = array();
    $edit['comment_body[0][value]'] = 'Foo';
    $edit['captcha_keypad_input'] = 'testing';
    $edit['captcha_keypad_keypad_used'] = 'Yes';
    $this->drupalPostForm($this->getUrl(), $edit, t('Save'));
    $this->assertText('Your comment has been posted.');
  }
}
