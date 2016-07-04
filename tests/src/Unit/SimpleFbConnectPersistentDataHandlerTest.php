<?php

/**
 * @file
 * Contains Drupal\Tests\simple_fb_connect\Unit\SimpleFbConnectPersistentDataHandlerTest.
 */

namespace Drupal\Tests\simple_fb_connect\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\simple_fb_connect\SimpleFbConnectPersistentDataHandler;

/**
 * @coversDefaultClass Drupal\simple_fb_connect\SimpleFbConnectPersistentDataHandler
 * @group simple_fb_connect
 */
class SimpleFbConnectPersistentDataHandlerTest extends UnitTestCase {

  protected $session;
  protected $persistentDataHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

    $this->persistentDataHandler = new SimpleFbConnectPersistentDataHandler(
      $this->session
    );
  }

  /**
   * Tests get().
   *
   * @covers ::get
   */
  public function testGet() {

    $this->session
      ->expects($this->once())
      ->method('get')
      ->with('simple_fb_connect_example_key')
      ->willReturn('hello world');

    $this->assertEquals('hello world', $this->persistentDataHandler->get('example_key'));
  }

}
