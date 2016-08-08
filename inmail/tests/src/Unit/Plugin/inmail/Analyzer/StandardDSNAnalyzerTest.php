<?php

namespace Drupal\Tests\inmail\Unit\Plugin\inmail\Analyzer;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\inmail\BounceAnalyzerResult;
use Drupal\inmail\MIME\Parser;
use Drupal\inmail\Plugin\inmail\Analyzer\StandardDSNAnalyzer;
use Drupal\inmail\ProcessorResult;
use Drupal\Tests\inmail\Unit\InmailUnitTestBase;

/**
 * Unit tests the DSN bounce message analyzer.
 *
 * @coversDefaultClass \Drupal\inmail\Plugin\inmail\Analyzer\StandardDSNAnalyzer
 *
 * @group inmail
 */
class StandardDSNAnalyzerTest extends InmailUnitTestBase {

  /**
   * Tests the analyze method.
   *
   * @covers ::analyze
   *
   * @dataProvider provideExpectedResults
   */
  public function testAnalyze($filename, $expected_code, $expected_recipient) {
    $message = (new Parser(new LoggerChannel('test')))->parseMessage($this->getRaw($filename));

    // Run the analyzer.
    $analyzer = new StandardDSNAnalyzer(array(), $this->randomMachineName(), array());
    $processor_result = new ProcessorResult();
    $analyzer->analyze($message, $processor_result);
    /** @var \Drupal\inmail\BounceAnalyzerResult $result */
    $result = $processor_result->getAnalyzerResult(BounceAnalyzerResult::TOPIC);

    // No result object if nothing to report.
    if (!isset($expected_code) && !isset($expected_recipient)) {
      $this->assertNull($result);
    }

    // Test the reported code.
    if (isset($expected_code)) {
      $this->assertEquals($expected_code, $result->getStatusCode()->getCode());
    }

    // Test the reported target recipient.
    if (isset($expected_recipient)) {
      $this->assertEquals($expected_recipient, $result->getRecipient());
    }
  }

  /**
   * Provides expected analysis results for test message files.
   */
  public function provideExpectedResults() {
    return [
      ['accessdenied.eml', '5.0.0', 'user@example.org'],
      ['full.eml', '4.2.2', 'user@example.org'],
      ['normal.eml', NULL, NULL],
      ['nouser.eml', '5.1.1', 'user@example.org'],
    ];
  }

}
