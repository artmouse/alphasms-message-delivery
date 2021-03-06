<?php

namespace Wearesho\Delivery\AlphaSms\Tests;

use GuzzleHttp;
use PHPUnit\Framework\TestCase;
use Wearesho\Delivery;

/**
 * Class ServiceTest
 * @package Wearesho\Delivery\AlphaSms\Tests
 * @coversDefaultClass \Wearesho\Delivery\AlphaSms\Service
 */
class ServiceTest extends TestCase
{
    /** @var Delivery\AlphaSms\Service */
    protected $service;

    /** @var Delivery\AlphaSms\Config */
    protected $config;

    /** @var GuzzleHttp\Handler\MockHandler */
    protected $mock;

    /** @var array */
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new Delivery\AlphaSms\Config();
        $this->config->login = 'Login';
        $this->config->password = 'Password';

        $this->mock = new GuzzleHttp\Handler\MockHandler();
        $this->container = [];
        $history = GuzzleHttp\Middleware::history($this->container);

        $stack = new GuzzleHttp\HandlerStack($this->mock);
        $stack->push($history);

        $this->service = new Delivery\AlphaSms\Service($this->config, new GuzzleHttp\Client([
            'handler' => $stack,
        ]));
    }

    public function testSendMessage(): void
    {
        $this->mock->append(
            new GuzzleHttp\Psr7\Response(200, [], 'id:1')
        );
        $message = new Delivery\Message('Some Text', '380000000000');
        $this->service->send($message);

        /** @var GuzzleHttp\Psr7\Request $request */
        $request = $this->container[0]['request'];
        $this->assertEquals(
            '<?xml version="1.0"?>
<package login="Login" password="Password"><message><msg recipient="380000000000" sender="test" type="0">Some Text</msg></message></package>' // phpcs:ignore
            . '
',
            (string)$request->getBody()
        );
    }

    /**
     * @expectedException \Wearesho\Delivery\Exception
     * @expectedExceptionMessage Unsupported recipient format
     */
    public function testInvalidRecipient(): void
    {
        $message = new Delivery\Message("Text", "123");
        $this->service->send($message);
    }
}
