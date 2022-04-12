<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\TwoFactorAuth\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\TwoFactorAuth\API;
use Piwik\Plugins\TwoFactorAuth\Dao\RecoveryCodeDao;
use Piwik\Plugins\TwoFactorAuth\TwoFactorAuthentication;
use Piwik\Plugins\UsersManager\API as UsersAPI;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group TwoFactorAuth
 * @group APITest
 * @group Plugins
 */
class APITest extends IntegrationTestCase
{
    /**
     * @var API
     */
    private $api;

    /**
     * @var RecoveryCodeDao
     */
    private $recoveryCodes;

    /**
     * @var TwoFactorAuthentication
     */
    private $twoFa;

    public function setUp(): void
    {
        parent::setUp();

        $this->api = API::getInstance();
        $this->recoveryCodes = StaticContainer::get(RecoveryCodeDao::class);

        foreach ([1,2,3] as $idsite) {
            Fixture::createWebsite('2014-01-02 03:04:05');
        }

        foreach (['mylogin1', 'mylogin2', 'login'] as $user) {
            UsersAPI::getInstance()->addUser($user, '123abcDk3_l3', $user . '@matomo.org');
        }
        $this->twoFa = StaticContainer::get(TwoFactorAuthentication::class);
    }

    public function test_resetTwoFactorAuth_failsWhenNotPermissions()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('checkUserHasSuperUserAccess Fake exception');

        $this->setAdminUser();
        $this->api->resetTwoFactorAuth('login', 'superUserPass');
    }

    public function test_resetTwoFactorAuth_resetsSecret()
    {
        $this->recoveryCodes->createRecoveryCodesForLogin('mylogin1');
        $this->recoveryCodes->createRecoveryCodesForLogin('mylogin2');
        $this->twoFa->saveSecret('mylogin1', '1234');
        $this->twoFa->saveSecret('mylogin2', '1234');

        $this->assertTrue(TwoFactorAuthentication::isUserUsingTwoFactorAuthentication('mylogin1'));
        $this->assertTrue(TwoFactorAuthentication::isUserUsingTwoFactorAuthentication('mylogin2'));
        $this->api->resetTwoFactorAuth('mylogin1', 'superUserPass');
        $this->assertFalse(TwoFactorAuthentication::isUserUsingTwoFactorAuthentication('mylogin1'));
        $this->assertTrue(TwoFactorAuthentication::isUserUsingTwoFactorAuthentication('mylogin2'));

        $this->assertEquals([], $this->recoveryCodes->getAllRecoveryCodesForLogin('mylogin1'));
    }

    protected function setAdminUser()
    {
        FakeAccess::clearAccess(false);
        FakeAccess::$identity = 'testUser';
        FakeAccess::$idSitesView = array();
        FakeAccess::$idSitesAdmin = array(1,2,3);
    }

    public function provideContainerConfig()
    {
        return array(
            'Piwik\Access' => new FakeAccess()
        );
    }

    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);
        $fixture->createSuperUser = true;
    }
}
