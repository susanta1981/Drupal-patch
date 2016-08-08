<?php

namespace Drupal\inmail\Plugin\inmail\Analyzer;

use Drupal\inmail\BounceAnalyzerResult;
use Drupal\inmail\MIME\DSNEntity;
use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Extracts the human-readable message from a DSN message.
 *
 * @todo Drop standard intro texts https://www.drupal.org/node/2379917
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "dsn_reason",
 *   label = @Translation("Standard DSN Reason Analyzer")
 * )
 */
class StandardDSNReasonAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MessageInterface $message, ProcessorResultInterface $processor_result) {
    // Ignore messages that are not DSN.
    if (!$message instanceof DSNEntity) {
      return;
    }

    /** @var \Drupal\inmail\BounceAnalyzerResult $result */
    $result = $processor_result->ensureAnalyzerResult(BounceAnalyzerResult::TOPIC, BounceAnalyzerResult::createFactory());

    // Save the human-readable bounce reason.
    $result->setReason(trim($message->getHumanPart()->getDecodedBody()));
  }
}
