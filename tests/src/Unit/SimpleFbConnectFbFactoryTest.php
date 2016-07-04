<?php

/**
 * @file
 * Contains Drupal\Tests\simple_fb_connect\Unit\SimpleFbConnectFbFactoryTest.
 */

namespace Drupal\Tests\simple_fb_connect\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\simple_fb_connect\SimpleFbConnectFbFactory;

/**
 * @coversDefaultClass Drupal\simple_fb_connect\SimpleFbConnectFbFactory
 * @group simple_fb_connect
 */
class SimpleFbConnectFbFactoryTest extends UnitTestCase {

  protected $configFactory;
  protected $loggerFactory;
  protected $persistentDataHandler;
  protected $fbFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->loggerFactory = $this->getMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');

    $this->persistentDataHandler = $this->getMockBuilder('Drupal\simple_fb_connect\SimpleFbConnectPersistentDataHandler')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Creates mocks with desired configFactory parameters.
   */
  protected function finalizeSetup($app_id, $app_secret) {
    $this->configFactory = $this->getConfigFactoryStub(
      array(
        'simple_fb_connect.settings' => array(
          'app_id' => $app_id,
          'app_secret' => $app_secret,
        ),
      )
    );

    $this->fbFactory = new SimpleFbConnectFbFactory(
      $this->configFactory,
      $this->loggerFactory,
      $this->persistentDataHandler
    );
  }

  /**
   * Tests getFbService when app ID and app Secrete have been set.
   *
   * @covers ::getFbService
   * @covers ::validateConfig
   * @covers ::getAppId
   * @covers ::getAppSecret
   */
  public function testGetFbServiceWithGoodData() {
    $this->finalizeSetup('123', 'abc');
    $this->assertInstanceOf('Facebook\Facebook', $this->fbFactory->getFbService());
  }

  /**
   * Tests getFbService with bad data.
   *
   * @covers ::getFbService
   * @covers ::validateConfig
   * @covers ::getAppId
   * @covers ::getAppSecret
   *
   * @dataProvider getFbServiceBadDataProvider
   */
  public function testGetFbServiceWithBadData($app_id, $app_secret) {
    $logger_channel = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->getMock();

    $this->loggerFactory
      ->expects($this->any())
      ->method('get')
      ->with('simple_fb_connect')
      ->willReturn($logger_channel);

    $this->finalizeSetup($app_id, $app_secret);
    $this->assertFalse($this->fbFactory->getFbService());
  }

    /**
   * Data provider for testLoginUser().
   *
   * @return array
   *   Nested arrays of values to check.
   *
   * @see ::testLoginuser()
   */
  public function getFbServiceBadDataProvider() {
    return array(
      array(NULL, NULL),
      array('', ''),
      array('123', NULL),
      array(NULL, 'abc'),
      array('123', ''),
      array(NULL, ''),
    );
  }

}
