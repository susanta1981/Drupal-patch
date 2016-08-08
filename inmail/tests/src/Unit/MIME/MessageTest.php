<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\inmail\MIME\Entity;
use Drupal\inmail\MIME\Header;
use Drupal\inmail\MIME\Message;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MIME Message class.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\Message
 *
 * @group inmail
 */
class MessageTest extends UnitTestCase {

  /**
   * Tests the message ID getter.
   *
   * @covers ::getMessageId
   */
  public function testGetMessageId() {
    $message = new Message(new Header([['name' => 'Message-ID', 'body' => '<Foo@example.com>']]), 'Bar');
    $this->assertEquals('<Foo@example.com>', $message->getMessageId());
  }

  /**
   * Tests the subject getter.
   *
   * @covers ::getSubject
   */
  public function testGetSubject() {
    $message = new Message(new Header([['name' => 'Subject', 'body' => 'Foo']]), 'Bar');
    $this->assertEquals('Foo', $message->getSubject());
  }

  /**
   * Tests the recipient getter.
   *
   * @covers ::getTo
   */
  public function testGetTo() {
    $message = new Message(new Header([['name' => 'To', 'body' => 'Foo']]), 'Bar');
    $this->assertEquals('Foo', $message->getTo());
  }

  /**
   * Tests the sender getter.
   *
   * @covers ::getFrom
   */
  public function testGetFrom() {
    $message = new Message(new Header([['name' => 'From', 'body' => 'Foo']]), 'Bar');
    $this->assertEquals('Foo', $message->getFrom());
  }

  /**
   * Tests the 'Received' date getter.
   *
   * @covers ::getReceivedDate
   */
  public function testGetReceivedDate() {
    $message = new Message(new Header([
      ['name' => 'Received', 'body' => 'blah; Thu, 29 Jan 2015 15:43:04 +0100'],
    ]), 'I am a body');

    $expected_date = new DateTimePlus('Thu, 29 Jan 2015 15:43:04 +0100');

    $this->assertEquals($expected_date, $message->getReceivedDate());
  }

}
