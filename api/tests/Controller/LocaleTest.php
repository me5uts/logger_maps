<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Controller;

use uLogger\Controller;

class LocaleTest extends AbstractControllerTestCase
{
  private Controller\Locale $controller;

  protected function setUp(): void {
    parent::setUp();
    $this->controller = new Controller\Locale($this->mapperFactory, $this->session, $this->config);
  }

  public function testGetLocaleSuccess() {
    $this->config->lang = 'pl';

    $response = $this->controller->get();

    $this->assertResponseSuccessWithAnyPayload($response);
    $this->assertEquals('Trasa', $response->getPayload()['track']);
  }

}
