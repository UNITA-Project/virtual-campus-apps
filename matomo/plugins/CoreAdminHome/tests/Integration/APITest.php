<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CoreAdminHome\tests\Integration;

use Piwik\Plugins\CoreAdminHome\API;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;

/**
 * @group CoreAdminHome
 * @group APITest
 * @group API
 * @group Plugins
 */
class APITest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    /**
     * @var int
     */
    private $idSite;

    /**
     * @var API
     */
    private $api;

    public function setUp(): void
    {
        parent::setUp();
        $this->api = API::getInstance();
        for ($i = 0; $i < 5; $i++) {
            Fixture::createWebsite('2014-01-02 03:04:05');
        }
    }

    public function test_getTrackingFailures_failsForViewUser()
    {
        $this->expectException(\Piwik\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasSomeAdminAccess');

        $this->setUser();
        $this->api->getTrackingFailures();
    }

    public function test_getTrackingFailures_WorksForAdminAndSuperuser()
    {
        $this->setAdminUser();
        $this->assertSame(array(), $this->api->getTrackingFailures());
        $this->setSuperUser();
        $this->api->getTrackingFailures();
        $this->assertSame(array(), $this->api->getTrackingFailures());
    }

    public function test_deleteAllTrackingFailures_failsForViewUser()
    {
        $this->expectException(\Piwik\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasSomeAdminAccess');

        $this->setUser();
        $this->api->deleteAllTrackingFailures();
    }

    public function test_deleteAllTrackingFailures_WorksForAdminAndSuperuser()
    {
        $this->expectNotToPerformAssertions();
        $this->setAdminUser();
        $this->api->deleteAllTrackingFailures();
        $this->setSuperUser();
        $this->api->deleteAllTrackingFailures();
    }

    public function test_deleteTrackingFailure_failsForViewUser()
    {
        $this->expectException(\Piwik\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasAdminAccess');

        $this->setUser();
        $this->api->deleteTrackingFailure(1, 2);
    }

    public function test_deleteTrackingFailure_failsForAdminUserIfNotAdminAccessToThatSite()
    {
        $this->expectException(\Piwik\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasAdminAccess');

        $this->setAdminUser();
        $this->api->deleteTrackingFailure(2, 2);
    }

    public function test_deleteTrackingFailure_WorksForAdminAndSuperuser()
    {
        $this->expectNotToPerformAssertions();
        $this->setAdminUser();
        $this->api->deleteTrackingFailure(1, 2);
        $this->setSuperUser();
        $this->api->deleteTrackingFailure(1, 2);
    }

    protected function setSuperUser()
    {
        FakeAccess::clearAccess(true);
    }

    protected function setUser()
    {
        FakeAccess::clearAccess(false);
        FakeAccess::$identity = 'testUser';
        FakeAccess::$idSitesView = array(1,3, $this->idSite);
        FakeAccess::$idSitesAdmin = array();
    }

    protected function setAdminUser()
    {
        FakeAccess::clearAccess(false);
        FakeAccess::$identity = 'testUser';
        FakeAccess::$idSitesView = array();
        FakeAccess::$idSitesAdmin = array(1,3, $this->idSite);
    }

    public function provideContainerConfig()
    {
        return array(
            'Piwik\Access' => new FakeAccess()
        );
    }
}
