<?php

namespace Drupal\inmail\MIME;

/**
 * An abstraction of a MIME entity of content type Multipart.
 *
 * The Multipart content type is defined in RFC 2046. It is used to combine
 * multiple entities of separate types, e.g. to include attachments in messages.
 *
 * @see https://tools.ietf.org/html/rfc2046#section-5
 *
 * @ingroup mime
 */
class MultipartEntity extends Entity {

  /**
   * The constituting parts.
   *
   * @var \Drupal\inmail\MIME\EntityInterface[]
   */
  protected $parts;

  /**
   * Decorates an entity into a multipart entity object.
   *
   * @param \Drupal\inmail\MIME\Entity $entity
   *   A MIME entity.
   * @param \Drupal\inmail\MIME\Entity[] $parts
   *   The parts constituting the body of $entity.
   */
  public function __construct(Entity $entity, array $parts) {
    parent::__construct($entity->header, $entity->body);
    $this->parts = $parts;
  }

  /**
   * Returns the indicated part.
   *
   * @param int $index
   *   The index of the part to get.
   *
   * @return \Drupal\inmail\MIME\EntityInterface
   *   The part at the given index, or NULL if the index is invalid.
   */
  public function getPart($index) {
    return isset($this->parts[$index]) ? $this->parts[$index] : NULL;
  }

  /**
   * Returns all contained parts.
   *
   * @return EntityInterface[]
   *   A list of the parts.
   */
  public function getParts() {
    return $this->parts;
  }

}
