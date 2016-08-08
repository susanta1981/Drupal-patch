<?php

namespace Drupal\inmail\MIME;

/**
 * Defines methods for a parser of MIME (email) messages.
 *
 * The MIME standards define an email message more generally as an "entity". An
 * MIME entity consists of a header and a body. The header in turn is a list of
 * header fields. The type of the body is defined by the Content-Type header
 * field. By default it is 7bit ASCII text.
 *
 * @ingroup mime
 */
interface ParserInterface {

  /**
   * Parses a string message into a Message object.
   *
   * @param string $raw
   *
   * @return \Drupal\inmail\MIME\MessageInterface
   *   The resulting Message object abstraction.
   *
   * @throws \Drupal\inmail\MIME\ParseException
   *   If parsing fails.
   */
  public function parseMessage($raw);

}
