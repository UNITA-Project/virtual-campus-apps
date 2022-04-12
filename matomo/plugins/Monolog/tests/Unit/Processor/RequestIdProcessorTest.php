<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Monolog\tests\Unit\Processor;

use PHPUnit\Framework\TestCase;
use Piwik\Common;
use Piwik\Plugins\Monolog\Processor\RequestIdProcessor;

/**
 * @group Log
 * @covers \Piwik\Plugins\Monolog\Processor\RequestIdProcessor
 */
class RequestIdProcessorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Common::$isCliMode = false;
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Common::$isCliMode = true;
    }

    /**
     * @test
     */
    public function it_should_append_request_id_to_extra()
    {
        $processor = new RequestIdProcessor();

        $result = $processor(array());

        $this->assertArrayHasKey('request_id', $result['extra']);
        self::assertIsString($result['extra']['request_id']);
        $this->assertNotEmpty($result['extra']['request_id']);
    }

    /**
     * @test
     */
    public function request_id_should_stay_the_same()
    {
        $processor = new RequestIdProcessor();

        $result = $processor(array());
        $id1 = $result['extra']['request_id'];

        $result = $processor(array());
        $id2 = $result['extra']['request_id'];

        $this->assertEquals($id1, $id2);
    }
}
