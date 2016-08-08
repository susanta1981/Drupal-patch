<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\inmail\MIME\Header;
use Drupal\inmail\MIME\Parser;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MIME Header class.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\Header
 *
 * @group inmail
 */
class HeaderTest extends UnitTestCase {

  /**
   * Tests string serialization.
   *
   * @covers ::toString
   *
   * @dataProvider provideHeaders
   */
  public function testToString(Header $header, $string) {
    $this->assertEquals($string, $header->toString());
  }

  /**
   * Tests header parsing.
   *
   * @covers \Drupal\inmail\MIME\Parser::parseHeaderFields
   *
   * @dataProvider provideHeaders
   */
  public function testParse(Header $header, $string) {
    $this->assertEquals($header, (new Parser(new LoggerChannel('')))->parseHeaderFields($string));
  }

  /**
   * Tests if there is a field.
   *
   * @covers ::hasField
   *
   * @dataProvider provideHeadersHasField
   */
  public function testHasField(Header $header, $expected) {
    $this->assertEquals($expected, $header->hasField('Content-Type'));
  }

  /**
   * Provides header objects for testing testHasField().
   *
   * @return array
   *   Header objects and expected representations.
   */
  public function provideHeadersHasField() {
    return [
      [
        new Header([[
          'name' => 'Content-Type',
          'body' => 'Multipart/Report; report-type=delivery-status; boundary="========/528515BF03161E46/smtp-in13.han.skanova.net"',
        ]]),
        TRUE,
      ],
      [
        new Header([[
          'name' => 'content-type',
          'body' => 'Multipart/Report; report-type=delivery-status; boundary="========/528515BF03161E46/smtp-in13.han.skanova.net"',
        ]]),
        TRUE,
      ],
      [
        new Header(),
        FALSE,
      ],
    ];
  }

  /**
   * Provides header objects for testing toString().
   *
   * @return array
   *   Header objects and equivalent string representations.
   */
  public function provideHeaders() {
    return [
      [
        new Header([[
          'name' => 'Content-Type',
          'body' => 'Multipart/Report; report-type=delivery-status; boundary="========/528515BF03161E46/smtp-in13.han.skanova.net"',
        ]]),
        // The 78 char limit is somewhere in the middle of the boundary. The
        // line folding algorithm must break before the last space before that
        // limit.
        "Content-Type: Multipart/Report; report-type=delivery-status;\n"
        . " boundary=\"========/528515BF03161E46/smtp-in13.han.skanova.net\"",
      ],
      [
        new Header([[
          'name' => 'Subject',
          // The ü in this string triggers base64 encoding in toString. Encoded
          // string wraps within the 78 char line limit.
          'body' => "Alle Menschen sind frei und gleich an Würde und Rechten geboren. Sie sind mit Vernunft und Gewissen begabt und sollen einander im Geist der Brüderlichkeit begegnen.",
        ]]),
        "Subject: =?UTF-8?B?QWxsZSBNZW5zY2hlbiBzaW5kIGZyZWkgdW5kIGdsZWljaCBhbiBX?=\n"
        . " =?UTF-8?B?w7xyZGUgdW5kIFJlY2h0ZW4gZ2Vib3Jlbi4gU2llIHNpbmQgbWl0IFZlcm51bmY=?=\n"
        . " =?UTF-8?B?dCB1bmQgR2V3aXNzZW4gYmVnYWJ0IHVuZCBzb2xsZW4gZWluYW5kZXIgaW0gR2U=?=\n"
        . " =?UTF-8?B?aXN0IGRlciBCcsO8ZGVybGljaGtlaXQgYmVnZWduZW4u?=",
      ],
    ];
  }

}
