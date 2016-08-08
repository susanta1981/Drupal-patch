<?php

namespace Drupal\inmail;

use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\user\UserInterface;

/**
 * A default analyzer result created to allow collaboration between analyzers.
 *
 * @ingroup analyzer
 */
interface DefaultAnalyzerResultInterface {

  /**
   * Sets the sender mail address.
   *
   * @param string $sender
   *   The address of the sender.
   */
  public function setSender($sender);

  /**
   * Returns the sender of the message.
   *
   * @return string|null
   *   The address of the sender, or NULL if it is found.
   */
  public function getSender();

  /**
   * Sets the account.
   *
   * @param \Drupal\user\UserInterface $user
   *   The new user.
   */
  public function setAccount(UserInterface $user);

  /**
   * Returns a user object.
   *
   * @return \Drupal\user\UserInterface
   *   The user object.
   */
  public function getAccount();

  /**
   * Tells the status of user authentication.
   *
   * @return bool
   *   TRUE if user is authenticated. Otherwise, FALSE;
   */
  public function isUserAuthenticated();

  /**
   * Returns the analyzed body of the message.
   *
   * @return string
   *   The analyzed body of the message.
   */
  public function getBody();

  /**
   * Sets the analyzed message body.
   *
   * @param string $body
   *   The analyzed message body.
   */
  public function setBody($body);

  /**
   * Returns the message footer.
   *
   * @return string
   *   The footer of the message.
   */
  public function getFooter();

  /**
   * Sets the message footer.
   *
   * @param string $footer
   *   The message footer.
   */
  public function setFooter($footer);

  /**
   * Returns the analyzed message subject.
   *
   * @return string
   *   The subject of the message.
   */
  public function getSubject();

  /**
   * Sets the actual message subject.
   *
   * @param string $subject
   *   The analyzed message subject.
   */
  public function setSubject($subject);

  /**
   * Sets the condition context for a given name.
   *
   * @param string $name
   *   The name of the context.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The context to set.
   */
  public function setContext($name, ContextInterface $context);

  /**
   * Gets the values for all defined contexts.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   An array of set contexts, keyed by context name.
   */
  public function getAllContexts();

  /**
   * Gets a specific context from the list of available contexts.
   *
   * @param string $name
   *   The name of the context to return.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface|null $context
   *   Requested context object or NULL if not found.
   *
   * @throws \InvalidArgumentException
   *   Throws an exception if requested context does not exist.
   */
  public function getContext($name);

  /**
   * Returns whether the context exists.
   *
   * @param string $name
   *   The name of the context.
   *
   * @return bool
   *   TRUE if the context exists. Otherwise, FALSE.
   */
  public function hasContext($name);

  /**
   * Returns contexts with the given type.
   *
   * @param string $type
   *   The context type.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   */
  public function getContextsWithType($type);

}
