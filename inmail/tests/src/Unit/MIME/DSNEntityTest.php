<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\inmail\MIME\Entity;
use Drupal\inmail\MIME\Header;
use Drupal\inmail\MIME\Parser;
use Drupal\inmail\MIME\DSNEntity;
use Drupal\inmail\MIME\MultipartEntity;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Parser, Entity and DSNEntity classes.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\DSNEntity
 *
 * @group inmail
 */
class DSNEntityTest extends UnitTestCase {

  /**
   * Minimal example DSN message.
   *
   * @var string
   */
  const MSG_DSN = <<<EOF
Content-type: multipart/report; report-type=delivery-status; boundary="boundary"

--boundary

Your message could not be delivered.
--boundary
Content-Type: message/delivery-status

Reporting-MTA: dns; example.com

Final-Recipient: rfc822; user@example.org
Action: failed
Status: 5.0.0

--boundary
Content-Type: message/rfc822

Subject: My very urgent message

--boundary--
EOF;

  /**
   * Tests the parser.
   *
   * @covers \Drupal\inmail\MIME\Parser::parseMessage
   */
  public function testParse() {
    // Parse and compare.
    $parsed_message = (new Parser(new LoggerChannel('test')))->parseMessage(static::MSG_DSN);
    $this->assertEquals(static::getMessage(), $parsed_message);
  }

  /**
   * @covers ::getHumanPart
   */
  public function testGetHumanPart() {
    $this->assertEquals(static::getHumanPart(), static::getMessage()->getHumanPart());
  }

  /**
   * @covers ::getStatusPart
   */
  public function testGetMachinePart() {
    $this->assertEquals(static::getStatusPart(), static::getMessage()->getStatusPart());
  }

  /**
   * @covers ::getOriginalPart
   */
  public function testGetOriginalPart() {
    $this->assertEquals(static::getOriginalPart(), static::getMessage()->getOriginalPart());
  }

  /**
   * @covers ::getPerMessageFields
   */
  public function testGetPerMessageFields() {
    $this->assertEquals(static::getPerMessageFields(), static::getMessage()->getPerMessageFields());
  }

  /**
   * @covers ::getPerRecipientFields
   */
  public function testGetPerRecipientFields() {
    $this->assertEquals(static::getPerRecipientField(), static::getMessage()->getPerRecipientFields(0));
    $this->assertEquals(NULL, static::getMessage()->getPerRecipientFields(1));
  }

  /**
   * Expected parse result of ::MSG_DSN.
   */
  protected static function getMessage() {
    // The multipart message corresponding to the final parse result.
    return new DSNEntity(
      new MultipartEntity(
        new Entity(
          static::getMessageHeader(),
          static::getBody()
        ),
        static::getParts()
      ),
      static::getDsnFields()
    );
  }

  /**
   * Expected parse result of the header of the message (the outer entity).
   */
  protected static function getMessageHeader() {
    return new Header([
      ['name' => 'Content-type', 'body' => 'multipart/report; report-type=delivery-status; boundary="boundary"'],
    ]);
  }

  /**
   * Expected parse result of the body of the message.
   */
  protected static function getBody() {
    return '--boundary

Your message could not be delivered.
--boundary
Content-Type: message/delivery-status

Reporting-MTA: dns; example.com

Final-Recipient: rfc822; user@example.org
Action: failed
Status: 5.0.0

--boundary
Content-Type: message/rfc822

Subject: My very urgent message

--boundary--';
  }

  /**
   * Expected parse result of the parts of the message.
   */
  protected static function getParts() {
    return [
      static::getHumanPart(),
      static::getStatusPart(),
      static::getOriginalPart(),
    ];
  }

  /**
   * Expected parse result of the first part of the message.
   */
  protected static function getHumanPart() {
    return new Entity(new Header(), static::getHumanPartBody());
  }

  /**
   * Expected parse result of the body of the first part of the message.
   */
  protected static function getHumanPartBody() {
    return "Your message could not be delivered.";
  }

  /**
   * Expected parse result of the second part of the message.
   */
  protected static function getStatusPart() {
    return new Entity(static::getStatusPartHeader(), static::getStatusPartBody());
  }

  /**
   * Expected parse result of the header of the second part of the message.
   */
  protected static function getStatusPartHeader() {
    return new Header([
      ['name' => 'Content-Type', 'body' => 'message/delivery-status'],
    ]);
  }

  /**
   * Expected parse result of the body of the second part of the message.
   */
  protected static function getStatusPartBody() {
    return "Reporting-MTA: dns; example.com\n\nFinal-Recipient: rfc822; user@example.org\nAction: failed\nStatus: 5.0.0\n";
  }

  /**
   * Expected parse result of the fields in the second part of the message.
   */
  protected static function getDsnFields() {
    return [
      static::getPerMessageFields(),
      static::getPerRecipientField(),
    ];
  }

  /**
   * Expected parse result of the message status fields in the second part.
   */
  protected static function getPerMessageFields() {
    return new Header([
      ['name' => 'Reporting-MTA', 'body' => 'dns; example.com'],
    ]);
  }

  /**
   * Expected parse result of the receipient status fields in the second part.
   */
  protected static function getPerRecipientField() {
    return new Header([
      ['name' => 'Final-Recipient', 'body' => 'rfc822; user@example.org'],
      ['name' => 'Action', 'body' => 'failed'],
      ['name' => 'Status', 'body' => '5.0.0'],
    ]);
  }

  /**
   * Expected parse result of the third part of the message.
   */
  protected static function getOriginalPart() {
    return new Entity(static::getOriginalPartHeader(), static::getOriginalPartBody());
  }

  /**
   * Expected parse result of the header of the third part of the message.
   */
  protected static function getOriginalPartHeader() {
    return new Header([
      ['name' => 'Content-Type', 'body' => 'message/rfc822'],
    ]);
  }

  /**
   * Expected parse result of the body of the third part of the message.
   */
  protected static function getOriginalPartBody() {
    return "Subject: My very urgent message\n";
  }

}
