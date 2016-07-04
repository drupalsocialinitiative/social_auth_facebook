<?php

/**
 * @file
 * Contains Drupal\Tests\simple_fb_connect\Unit\SimpleFbConnectFbManagerTest.
 */

namespace Drupal\Tests\simple_fb_connect\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\simple_fb_connect\SimpleFbConnectFbManager;

/**
 * @coversDefaultClass Drupal\simple_fb_connect\SimpleFbConnectFbManager
 * @group simple_fb_connect
 */
class SimpleFbConnectFbManagerTest extends UnitTestCase {

  protected $loggerFactory;
  protected $eventDispatcher;
  protected $entityFieldManager;
  protected $urlGenerator;
  protected $persistentDataHandler;
  protected $facebook;
  protected $fbManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->loggerFactory = $this->getMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');

    $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

    $this->entityFieldManager = $this->getMock('Drupal\Core\Entity\EntityFieldManagerInterface');

    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');

    $this->persistentDataHandler = $this->getMockBuilder('Drupal\simple_fb_connect\SimpleFbConnectPersistentDataHandler')
      ->disableOriginalConstructor()
      ->getMock();

    $this->fbManager = new SimpleFbConnectFbManager(
      $this->loggerFactory,
      $this->eventDispatcher,
      $this->entityFieldManager,
      $this->urlGenerator,
      $this->persistentDataHandler
    );

    $this->facebook = $this->getMockBuilder('Facebook\Facebook')
      ->disableOriginalConstructor()
      ->getMock();
    $this->fbManager->setFacebookService($this->facebook);
  }

  // TODO: write the actual tests.

}
