<?php

/**
 * @file
 * Contains Drupal\Tests\simple_fb_connect\Unit\SimpleFbConnectPostLoginManagerTest.
 */

namespace Drupal\Tests\simple_fb_connect\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\simple_fb_connect\SimpleFbConnectPostLoginManager;

/**
 * @coversDefaultClass Drupal\simple_fb_connect\SimpleFbConnectPostLoginManager
 * @group simple_fb_connect
 */
class SimpleFbConnectPostLoginManagerTest extends UnitTestCase {

  protected $configFactory;
  protected $requestContext;
  protected $pathValidator;
  protected $persistentDataHandler;
  protected $postLoginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configFactory = $this->getConfigFactoryStub(
      array(
        'simple_fb_connect.settings' => array(
          'post_login_path' => '<front>',
        ),
      )
    );

    $this->requestContext = $this->getMock('Drupal\Core\Routing\RequestContext');

    $this->pathValidator = $this->getMockBuilder('Drupal\Core\Path\PathValidatorInterface')
      ->disableOriginalConstructor()
      ->setMethods(array('getUrlIfValid', 'toString'))
      ->getMockForAbstractClass();

    $this->persistentDataHandler = $this->getMockBuilder('Drupal\simple_fb_connect\SimpleFbConnectPersistentDataHandler')
      ->disableOriginalConstructor()
      ->getMock();

    $this->postLoginManager = new SimpleFbConnectPostLoginManager(
      $this->configFactory,
      $this->requestContext,
      $this->pathValidator,
      $this->persistentDataHandler
    );
  }

  /**
   * Tests getPostLoginPathFromRequest when value is set.
   *
   * @covers ::getPostLoginPathFromRequest
   *
   * @dataProvider getPostLoginPathFromRequestDataProvider
   */
  public function testGetPostLoginPathFromRequest($input, $expected) {
    $this->requestContext
      ->expects($this->once())
      ->method('getQueryString')
      ->willReturn($input);

    $this->assertSame($expected, $this->postLoginManager->getPostLoginPathFromRequest());
  }

  /**
   * Data provider for testPostLoginPathFromRequestWithValue().
   *
   * @return array
   *   Nested arrays of values to check
   *
   * @see ::testGetPostLoginPathFromRequest()
   */
  public function getPostLoginPathFromRequestDataProvider() {
    return array(
      array('postLoginPath=<front>', '<front>'),
      array('postLoginPath=node', 'node'),
      array('', FALSE),
      array(NULL, FALSE),
      array('something=else', FALSE),
    );
  }

  /**
   * Tests getPostLoginPath method with valid query parameter.
   *
   * @covers ::getPostLoginPath
   * @covers ::validateInternalPath
   */
  public function testGetPostLoginPathWithValidQueryParameter() {
    $query_path = 'node/1';
    $query_url = $this->generateStubUrl(FALSE, $query_path);

    $this->persistentDataHandler
      ->expects($this->once())
      ->method('get')
      ->with('post_login_path')
      ->willReturn($query_path);

    $this->pathValidator
      ->expects($this->once())
      ->method('getUrlIfValid')
      ->willReturn($query_url);

    $this->assertEquals($query_path, $this->postLoginManager->getPostLoginPath());
  }


  /**
   * Tests getPostLoginPath method with invalid query parameter.
   *
   * In this situation we are expected to use the path found in module settings.
   *
   * @covers ::getPostLoginPath
   * @covers ::validateInternalPath
   */
  public function testGetPostLoginPathWithInvalidQueryParameter() {
    // 1. Path from query parameter.
    $query_path = 'http://www.example.com';
    $this->persistentDataHandler
      ->expects($this->once())
      ->method('get')
      ->with('post_login_path')
      ->willReturn($query_path);
    $query_url = FALSE;

    // 2. Path from module settings.
    $module_path = $this->configFactory
      ->get('simple_fb_connect.settings')
      ->get('post_login_path');
    $module_url = $this->generateStubUrl(FALSE, $module_path);

    $this->pathValidator
      ->expects($this->any())
      ->method('getUrlIfValid')
      ->will($this->onConsecutiveCalls(
          $query_url,
          $module_url
        ));
    $this->assertEquals($module_path, $this->postLoginManager->getPostLoginPath());
  }

  /**
   * Tests getPostLoginPath method with fallback to 'user'.
   *
   * @covers ::getPostLoginPath
   * @covers ::validateInternalPath
   */
  public function testPostLoginPathWithInvalidModulePath() {
    // 1. Path from query parameter.
    $query_path = 'http://www.example.com';
    $this->persistentDataHandler
      ->expects($this->once())
      ->method('get')
      ->with('post_login_path')
      ->willReturn($query_path);
    $query_url = $this->generateStubUrl(TRUE, $query_path);

    // 2. Module settings has invalid path so pathValidator will return FALSE
    // instead of an URL object.
    $module_url = FALSE;

    // 3. Fallback to 'user'.
    $fallback_path = 'user';
    $fallback_url = $this->generateStubUrl(FALSE, $fallback_path);

    $this->pathValidator
      ->expects($this->any())
      ->method('getUrlIfValid')
      ->will($this->onConsecutiveCalls(
          $query_url,
          $module_url,
          $fallback_url
        ));

    $this->assertEquals($fallback_path, $this->postLoginManager->getPostLoginPath());
  }

  /**
   * Tests the getPathToUserForm() method.
   *
   * @covers ::getPathToUserForm
   */
  public function testGetPathToUserForm() {
    $user = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $user
      ->expects($this->any())
      ->method('id')
      ->willReturn('1');

    $this->pathValidator
      ->expects($this->once())
      ->method('getUrlIfValid')
      ->willReturn($this->pathValidator);

    $this->pathValidator
      ->expects($this->once())
      ->method('toString')
      ->willReturn('user/1/edit');

    $this->assertEquals('user/1/edit', $this->postLoginManager->getPathToUserForm($user));
  }

  /**
   * Helper function to generate stub Url objects.
   *
   * @param bool $external
   *   Value to be returned from 'isExternal' method.
   * @param string $path
   *   Value to be returned from 'toString' method.
   */
  protected function generateStubUrl($external, $path) {
    $url = $this->getMockBuilder('Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->setMethods(array('isExternal', 'toString'))
      ->getMock();

    $url
      ->expects($this->any())
      ->method('isExternal')
      ->willReturn($external);

    $url
      ->expects($this->any())
      ->method('toString')
      ->willReturn($path);

    return $url;
  }

}
