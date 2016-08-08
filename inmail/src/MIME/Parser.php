<?php

namespace Drupal\inmail\MIME;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Parser for MIME (email) messages.
 *
 * Parser::parseMessage() and Entity::toString() do not exactly invert each
 * other. Entity::toString() aims to produce results closely adhering to the
 * MIME standards, while the parser does not require full compliance to all its
 * recommendations. Notably, the length of folded lines may differ between raw
 * input and serialized output.
 *
 * The newline sequence used in MIME is CRLF ('\r\n'). To simplify processing,
 * however, the raw input is immediately converted to LF ('\n'). For example,
 * messages saved on the filesystem are likely to use LF. In other words, the
 * input can use either CRLF or LF, but output and API will use LF.
 *
 * @ingroup mime
 */
class Parser implements ParserInterface, ContainerInjectionInterface {

  /**
   * The injected logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * Creates a new Parser.
   */
  public function __construct(LoggerChannelInterface $logger_channel) {
    $this->loggerChannel = $logger_channel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('logger.channel.inmail'));
  }

  /**
   * Extracts names and email addresses from a header field.
   *
   * Some header fields, most notably To and Cc, may contain multiple addresses,
   * optionally including names.
   *
   * @param string $field
   *   The content of a To header or similar.
   *
   * @return array[]
   *   A list of associative arrays, each with the following elements:
   *     - name: the optional name before the address
   *     - address: the email address
   *
   * @see https://tools.ietf.org/html/rfc5322#section-3.4
   *
   * @todo Allow comma in name, https://www.drupal.org/node/2475057
   */
  public static function parseAddress($field) {
    // Separate by comma, each element is trimmed.
    $parts = preg_split('/\s*,\s*/', trim($field));
    $mailboxes = [];
    foreach ($parts as $part) {
      if (preg_match('/^\S+@\S+\.\S+$/', $part)) {
        // Match address "foo@example.com".
        $mailboxes[] = ['name' => '', 'address' => $part];
      }
      elseif (preg_match('/(.*)<(\S+@\S+\.\S+)>$/', $part, $matches)) {
        // Match name and address "Foo Bar <foo@example.com>".
        $mailboxes[] = ['name' => trim(trim($matches[1]), '"'), 'address' => $matches[2]];
      }
    }
    return $mailboxes;
  }

  /**
   * {@inheritdoc}
   */
  public function parseMessage($raw) {
    $entity = $this->parseEntity($raw);

    // The parsing may or may not deduce a specific type.
    // If it is Message (or more specific) it can be returned as is.
    if ($entity instanceof MessageInterface) {
      return $entity;
    }

    // If it is a MultipartEntity, we must create a MultipartMessage from it, to
    // satisfy the MessageInterface return type of this method.
    if ($entity instanceof MultipartEntity) {
      return new MultipartMessage($entity, $entity->getParts());
    }

    // If it has not been recognized as any specific type, we should at least
    // create a Message from it.
    return new Message($entity->getHeader(), $entity->getBody());
  }

  /**
   * Parses a string entity into a structured entity object.
   *
   * Note: This method is subject to change in order to support more Entity
   * types. See issue https://www.drupal.org/node/2389349
   *
   * The input can be a message or more generally a MIME entity.
   *
   * While the header section is required in a message, it is optional for
   * multipart parts, in which case the entity contains only the body, preceded
   * by a double CRLF.
   *
   * @param string $raw
   *   A string entity.
   *
   * @return \Drupal\inmail\MIME\EntityInterface
   *   The resulting Entity object abstraction.
   *
   * @throws \Drupal\inmail\MIME\ParseException
   *   If parsing fails.
   */
  protected function parseEntity($raw) {
    // Normalize to LF.
    $raw = str_replace("\r\n", "\n", $raw);

    // Parse to a basic entity.
    $entity = $this->parseBasicEntity($raw);

    // Identify a multipart entity and decorate the entity object.
    if ($this->isMultipart($entity)) {
      try {
        $entity = $this->parseMultipart($entity);
      }
      catch (ParseException $e) {
        // Parsing as multipart failed, log it and continue with the Entity
        // object.
        $this->loggerChannel->info('Message %message_id was identified as multipart but could not be parsed as such.', ['%message_id' => $entity->getHeader()->getFieldBody('Message-Id')]);
        // @todo Notify caller is about this problem.
      }
    }

    return $entity;
  }

  /**
   * Parses a raw entity into a basic entity object.
   *
   * Note: This method is subject to change in order to support more Entity
   * types. See issue https://www.drupal.org/node/2389349
   *
   * @param string $raw
   *   A raw entity.
   *
   * @return \Drupal\inmail\MIME\Entity
   *   The resulting entity object.
   *
   * @throws \Drupal\inmail\MIME\ParseException
   *   If parsing fails.
   */
  protected function parseBasicEntity($raw) {
    // Header is separated from body by a blank line.
    $header_body = preg_split("/(^|\n)\n/", $raw, 2);
    if (count($header_body) != 2) {
      throw new ParseException('Failed to split header from body');
    }
    list($header_raw, $body) = $header_body;

    // Parse raw header into Header object.
    $header = $this->parseHeaderFields($header_raw);

    return new Entity($header, $body);
  }

  /**
   * Parses an entity into a multipart entity.
   *
   * Note: This method is subject to change in order to support more Entity
   * types. See issue https://www.drupal.org/node/2389349
   *
   * @param \Drupal\inmail\MIME\Entity $entity
   *   A basic entity.
   *
   * @return \Drupal\inmail\MIME\MultipartEntity
   *   The resulting multipart entity object.
   *
   * @throws \Drupal\inmail\MIME\ParseException
   *   If parsing fails.
   */
  protected function parseMultipart(Entity $entity) {
    $parts = $this->extractMultipartParts($entity);
    $multipart_entity = new MultipartEntity($entity, $parts);

    // Identify a DSN message and decorate the entity object further.
    // @todo Move to pluggable component (plugin?) in https://www.drupal.org/node/2389349
    if ($this->isDsn($multipart_entity)) {
      try {
        $multipart_entity = $this->parseDsn($multipart_entity);
      }
      catch (ParseException $e) {
        // Parsing as DSN failed, log it and continue with the MultipartEntity
        // object.
        $this->loggerChannel->info('Message %message_id was identified as DSN but could not be parsed as such.', ['%message_id' => $multipart_entity->getHeader()->getFieldBody('Message-Id')]);
        // @todo Notify caller is about this problem.
      }
    }

    return $multipart_entity;
  }

  /**
   * Parses a multipart entity into a DSN entity.
   *
   * Note: This method is subject to change in order to support more Entity
   * types. See issue https://www.drupal.org/node/2389349
   *
   * @param \Drupal\inmail\MIME\MultipartEntity $multipart_entity
   *   A multipart entity.
   *
   * @return \Drupal\inmail\MIME\DSNEntity
   *   The resulting DSN entity object.
   *
   * @throws \Drupal\inmail\MIME\ParseException
   *   If parsing fails.
   */
  protected function parseDsn(MultipartEntity $multipart_entity) {
    // Parse the second part, which contains groups of fields having the
    // same syntax as header fields.
    $dsn_fields = array();
    $body = trim($multipart_entity->getPart(1)->getBody());
    if (strpos($body, "\n\n") === FALSE) {
      throw new ParseException('Blank line missing in delivery-status part');
    }
    foreach (explode("\n\n", $body) as $field_group) {
      $dsn_fields[] = $this->parseHeaderFields($field_group);
    }

    return new DSNEntity($multipart_entity, $dsn_fields);
  }

  /**
   * Checks if the entity is of content type "multipart".
   *
   * @param \Drupal\inmail\MIME\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity has type "multipart", otherwise FALSE.
   */
  protected function isMultipart(EntityInterface $entity) {
    $content_type = $entity->getContentType();
    return strtolower($content_type['type']) == 'multipart' && isset($content_type['parameters']['boundary']);
  }

  /**
   * Checks if the entity content-type implies a DSN message.
   *
   * Specified in RFC 3464, a Delivery Status Notification (DSN) has
   * content-type "multipart/report" with report-type "delivery-status". Those
   * values are case-insensitive.
   *
   * Note: This method is subject to change in order to support more Entity
   * types. See issue https://www.drupal.org/node/2389349
   *
   * @param \Drupal\inmail\MIME\MultipartEntity $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the content-type header field declares a DSN message, otherwise
   *   FALSE.
   *
   * @throws \Drupal\inmail\MIME\ParseException
   *   If parsing fails. This carries no judgment of whether the message is a
   *   DSN, because none could be made.
   */
  protected function isDsn(MultipartEntity $entity) {
    $content_type = $entity->getContentType();
    if (strtolower($content_type['subtype']) == 'report') {
      if (!isset($content_type['parameters']['report-type'])) {
        throw new ParseException('Parameter "report-type" missing in multipart entity content-type field');
      }
      return strtolower($content_type['parameters']['report-type']) == 'delivery-status';
    }
    return FALSE;
  }

  /**
   * Parses the body of a multipart entity into parts.
   *
   * This method must only be called if ::isMultipart() returns TRUE.
   *
   * The Multipart content type has a required 'boundary' parameter. The
   * boundary is used to separate the constituting parts in the body of the
   * entity.
   *
   * Each part is in turn parsed as an entity.
   *
   * @param \Drupal\inmail\MIME\EntityInterface $entity
   *   The entity to interpret as multipart.
   *
   * @return \Drupal\inmail\MIME\EntityInterface[]
   *   The constituting parts of the multipart message.
   *
   * @throws \Drupal\inmail\MIME\ParseException
   *   If parsing fails.
   */
  protected function extractMultipartParts(EntityInterface $entity) {
    // Identify the boundary string.
    $content_type = $entity->getContentType();
    if (!isset($content_type['parameters']['boundary'])) {
      throw new ParseException('No "boundary" parameter in content-type field');
    }
    $boundary = $content_type['parameters']['boundary'];

    // The last part is terminated by "--$boundary--".
    $parts = strstr($entity->getBody(), "\n--$boundary--", TRUE);
    if ($parts === FALSE) {
      throw new ParseException('Terminating boundary missing in multipart body');
    }
    // Prepend with newline to facilitate explosion.
    $parts = "\n$parts";
    // The parts are separated by "--$boundary".
    $parts = explode("\n--$boundary\n", $parts);
    if (empty($parts)) {
      throw new ParseException('Multipart body contains zero parts');
    }
    // The content before the first part is to be ignored.
    array_shift($parts);

    // Recursively parse each part.
    foreach ($parts as $key => $part) {
      $parts[$key] = $this->parseEntity($part);
    }
    return $parts;
  }

  /**
   * Parses a string header into a Header object.
   *
   * Header fields are separated by newlines followed by non-whitespace. If a
   * line begins with space, it is part of the previous header field.
   *
   * Passing an empty string is allowed, and results in an empty Header object.
   *
   * @param string $raw_header
   *   A string in the header format defined by RFC 2822 "Internet Message
   *   Format".
   *
   * @return \Drupal\inmail\MIME\Header
   *   The resulting Header object abstraction.
   *
   * @throws \Drupal\inmail\MIME\ParseException
   *   If parsing fails.
   *
   * @see https://tools.ietf.org/html/rfc2822#section-2.2
   */
  public function parseHeaderFields($raw_header) {
    $header = new Header();

    // In some entities, headers are optional.
    if (empty($raw_header)) {
      return $header;
    }

    // Header fields are separated by CRLF followed by non-whitespace.
    $fields = preg_split('/\n(?!\s)/', $raw_header);
    foreach ($fields as $field) {
      $name_body = explode(':', $field, 2);
      if (count($name_body) != 2) {
        throw new ParseException("Missing ':' in header field: $field");
      }
      list($name, $body) = $name_body;

      // Decode and unfold lines.
      $decoded_body = str_replace("\n", '', Unicode::mimeHeaderDecode(trim($body)));
      $header->addField(trim($name), $decoded_body, FALSE);
    }
    return $header;
  }

}
