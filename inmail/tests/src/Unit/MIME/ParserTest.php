<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\inmail\MIME\Parser;
use Drupal\Tests\inmail\Unit\InmailUnitTestBase;

/**
 * Tests the MIME Parser class.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\Parser
 *
 * @group inmail
 */
class ParserTest extends InmailUnitTestBase {

  /**
   * Tests that an exception is thrown when parsing fails.
   *
   * @covers ::parseMessage
   *
   * @dataProvider provideMalformedRaws
   *
   * @expectedException \Drupal\inmail\MIME\ParseException
   */
  public function testParseException($raw) {
    (new Parser(new LoggerChannel('test')))->parseMessage($raw);
  }

  /**
   * Provides invalid entities that should cause the parser to fail.
   */
  public function provideMalformedRaws() {
    return [
      [$this->getRaw('malformed/headerbody.eml')],
      // @todo Cover more cases of invalid messages.
    ];
  }

  /**
   * Test address parsing.
   *
   * @covers ::parseAddress
   *
   * @dataProvider provideAddresses
   */
  public function testParseAddress($field, $expected) {
    $this->assertEquals($expected, Parser::parseAddress($field));
  }

  /**
   * Provide email address fields to test parseAddress with.
   */
  public static function provideAddresses() {
    return [
      // Spaces.
      [' admin@example.com ', [
        ['name' => '', 'address' => 'admin@example.com'],
      ]],
      // Multiple.
      ['a@b.c, d.e@f.g.h', [
        ['name' => '', 'address' => 'a@b.c'],
        ['name' => '', 'address' => 'd.e@f.g.h'],
      ]],
      // With name.
      ['Admin <admin@example.com>', [
        ['name' => 'Admin', 'address' => 'admin@example.com'],
      ]],
      // With quote-enclosed name.
      ['"Admin" <admin@example.com>', [
        ['name' => 'Admin', 'address' => 'admin@example.com'],
      ]],
      // Multiple with name.
      ['Admin <admin@example.com>, User <user.name@users.example.com>', [
        ['name' => 'Admin', 'address' => 'admin@example.com'],
        ['name' => 'User', 'address' => 'user.name@users.example.com'],
      ]],
      // Comma in name (resolves to multiple, where first is invalid).
      ['Admin, Bedmin <admin@example.com>', [
        ['name' => 'Bedmin', 'address' => 'admin@example.com'],
      ]],
      // Address in quotes but not after (invalid).
      ['"Admin, Admin <admin@example.com>"', []],
      // @todo Allow comma in name, https://www.drupal.org/node/2475057
//      // Comma in name (quoted, valid).
//      ['"Admin, Admin" <admin@example.com>', [
//        ['name' => 'Admin, Admin', 'address' => 'admin@example.com'],
//      ]],
//      // Address in quotes and after.
//      ['"Admin, Admin <admin@example.com>" <admin@example.com>', [
//        ['name' => 'Admin <admin@example.com>', 'address' => 'admin@example.com'],
//      ]],
      // Unicode in name.
      ['Admin™ <admin@example.com>', [
        ['name' => 'Admin™', 'address' => 'admin@example.com'],
      ]],
      // Sub-address extension pattern.
      ['Admin <admin+admin@example.com>', [
        ['name' => 'Admin', 'address' => 'admin+admin@example.com'],
      ]],
    ];
  }

}
