<?php

namespace AndriesLouw\imagesweserv\Test\Manipulators;

use AndriesLouw\imagesweserv\Api\Api;
use AndriesLouw\imagesweserv\Client;
use AndriesLouw\imagesweserv\Manipulators\Trim;
use AndriesLouw\imagesweserv\Test\ImagesweservTestCase;
use Jcupitt\Vips\Image;
use Mockery\MockInterface;

class TrimTest extends ImagesweservTestCase
{
    /**
     * @var Client|MockInterface
     */
    private $client;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var Trim
     */
    private $manipulator;

    public function setUp()
    {
        $this->client = $this->getMockery(Client::class);
        $this->api = new Api($this->client, $this->getManipulators());
        $this->manipulator = new Trim();
    }

    public function testCreateInstance()
    {
        $this->assertInstanceOf(Trim::class, $this->manipulator);
    }

    public function testTrim()
    {
        $testImage = $this->inputPngOverlayLayer1;
        $expectedImage = $this->expectedDir . '/alpha-layer-1-fill-trim-resize.png';
        $params = [
            'w' => '450',
            'h' => '322',
            't' => 'square',
            'trim' => '25'
        ];

        $uri = basename($testImage);

        $this->client->shouldReceive('get')->with($uri)->andReturn($testImage);

        /** @var Image $image */
        $image = $this->api->run($uri, $params);

        $this->assertEquals(450, $image->width);
        $this->assertEquals(322, $image->height);
        $this->assertTrue($image->hasAlpha());

        $this->assertSimilarImage($expectedImage, $image);
    }

    public function testTrim16bitWithTransparency()
    {
        $testImage = $this->inputPngWithTransparency16bit;
        $expectedImage = $this->expectedDir . '/trim-16bit-rgba.png';
        $params = [
            'w' => '32',
            'h' => '32',
            't' => 'square',
            'trim' => '10'
        ];

        $uri = basename($testImage);

        $this->client->shouldReceive('get')->with($uri)->andReturn($testImage);

        /** @var Image $image */
        $image = $this->api->run($uri, $params);

        $this->assertEquals(4, $image->bands);
        $this->assertEquals(32, $image->width);
        $this->assertEquals(32, $image->height);
        $this->assertTrue($image->hasAlpha());
        $this->assertSimilarImage($expectedImage, $image);
    }

    public function testTrimSkipShrinkOnLoad()
    {
        $testImage = $this->inputJpgOverlayLayer2;
        $expectedImage = $this->expectedDir . '/alpha-layer-2-trim-resize.jpg';
        $params = [
            'w' => '300',
            'trim' => '10'
        ];

        $uri = basename($testImage);

        $this->client->shouldReceive('get')->with($uri)->andReturn($testImage);

        // @var Image $image
        $image = $this->api->run($uri, $params);

        $this->assertEquals(300, $image->width);
        $this->assertEquals(300, $image->height);
        $this->assertFalse($image->hasAlpha());
        $this->assertSimilarImage($expectedImage, $image);
    }

    /**
     * @expectedException \PHPUnit\Framework\Error\Warning
     */
    public function testAggressiveTrimTriggersError()
    {
        $testImage = $this->inputPngOverlayLayer0;
        $params = [
            'trim' => '200'
        ];

        $uri = basename($testImage);

        $this->client->shouldReceive('get')->with($uri)->andReturn($testImage);

        $this->api->run($uri, $params);
    }

    public function testAggressiveTrimReturnsOriginalImage()
    {
        $testImage = $this->inputPngOverlayLayer0;
        $params = [
            'trim' => '200'
        ];

        $uri = basename($testImage);

        $this->client->shouldReceive('get')->with($uri)->andReturn($testImage);

        /** @var Image $image */
        $image = @$this->api->run($uri, $params);

        // Check if dimensions is unchanged
        $this->assertEquals(2048, $image->width);
        $this->assertEquals(1536, $image->height);

        // Check if the image is unchanged
        $this->assertSimilarImage($testImage, $image);
    }

    public function testGetTrim()
    {
        $this->assertSame(50, $this->manipulator->setParams(['trim' => '50'])->getTrim());
        $this->assertSame(50, $this->manipulator->setParams(['trim' => 50.50])->getTrim());
        $this->assertSame(10, $this->manipulator->setParams(['trim' => null])->getTrim());
        $this->assertSame(10, $this->manipulator->setParams(['trim' => 'a'])->getTrim());
        $this->assertSame(10, $this->manipulator->setParams(['trim' => '-1'])->getTrim());
        $this->assertSame(10, $this->manipulator->setParams(['trim' => '256'])->getTrim());
    }
}
