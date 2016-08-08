<?php

namespace Drupal\Tests\inmail_collect\Kernel;

use Drupal\collect\Entity\Container;
use Drupal\collect\Entity\Model;
use Drupal\inmail\Plugin\DataType\Mailbox;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Inmail model plugin.
 *
 * @group inmail
 */
class InmailMessageModelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'inmail_collect',
    'inmail',
    'collect',
    'collect_common',
    'inmail_test',
    'rest',
    'serialization',
  ];

  /**
   * Tests the properties of the model plugin.
   *
   * @see Drupal\inmail_collect\Plugin\collect\Model\InmailMessage::evaluate()
   * @see Drupal\inmail_collect\Plugin\collect\Model\InmailMessage::getStaticPropertyDefinitions()
   */
  public function testProperties() {
    $raw = file_get_contents(\Drupal::root() . '/' . drupal_get_path('module', 'inmail_test') . '/eml/simple-autoreply.eml');
    $container = Container::create([
      'data' => json_encode([
        'raw' => $raw,
      ]),
      'schema_uri' => 'https://www.drupal.org/project/inmail/schema/message',
      'type' => 'application/json',
    ]);

    // Create suggested model.
    /** @var \Drupal\collect\Model\ModelManagerInterface $model_manager */
    $model_manager = \Drupal::service('plugin.manager.collect.model');
    $model = $model_manager->suggestModel($container);
    Model::create([
      'id' => 'email_model',
      'label' => $model->label(),
      'plugin_id' => $model->getPluginId(),
      'uri_pattern' => $model->getUriPattern(),
      'properties' => $model->getProperties(),
    ])->save();

    /** @var \Drupal\collect\TypedData\TypedDataProvider $typed_data_provider */
    $typed_data_provider = \Drupal::service('collect.typed_data_provider');
    $data = $typed_data_provider->getTypedData($container);

    // Each property of the model should map to data in the message.
    $this->assertTrue($data->get('from') instanceof Mailbox);
    $this->assertEqual(['name' => 'Nancy', 'address' => 'nancy@example.com'], $data->get('from')->getValue());
    $this->assertTrue($data->get('to')->get(0) instanceof Mailbox);
    $this->assertEqual([['name' => 'Arild', 'address' => 'arild@example.com']], $data->get('to')->getValue());
    $this->assertTrue($data->get('cc')->get(0) instanceof Mailbox);
    $this->assertEqual([['name' => 'Boss', 'address' => 'boss@example.com']], $data->get('cc')->getValue());
    $this->assertTrue($data->get('bcc')->get(0) instanceof Mailbox);
    $this->assertEqual([['name' => 'Big Brother', 'address' => 'bigbrother@example.com']], $data->get('bcc')->getValue());
    $this->assertEqual('Out of office', $data->get('subject')->getValue());
    $this->assertEqual("Hello\nI'm out of office due to illness", $data->get('body')->getValue());
    $this->assertEquals('Mail: Out of office', $data->get('_default_title')->getValue());
  }

}
