<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\Db;

use Piwik\Db;
use Piwik\Db\TransactionLevel;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group TransactionLevelTest
 * @group TransactionLevel
 * @group Plugins
 */
class TransactionLevelTest extends IntegrationTestCase
{
    /**
     * @var TransactionLevel
     */
    private $level;

    /**
     * @var \Piwik\Tracker\Db|\Piwik\Db\AdapterInterface|\Piwik\Db $db
     */
    private $db;

    public function setUp(): void
    {
        parent::setUp();
        $this->db    = Db::get();
        $this->level = new TransactionLevel($this->db);
    }

    public function test_canLikelySetTransactionLevel()
    {
        $this->assertTrue($this->level->canLikelySetTransactionLevel());
    }

    public function test_setUncommitted_restorePreviousStatus()
    {
        // mysql 8.0 using transaction_isolation
        $isolation = $this->db->fetchOne("SHOW GLOBAL VARIABLES LIKE 't%_isolation'");
        $isolation = "@@" . $isolation;

        $value = $this->db->fetchOne('SELECT ' . $isolation);
        $this->assertSame('REPEATABLE-READ', $value);

        $this->level->setUncommitted();
        $value = $this->db->fetchOne('SELECT ' . $isolation);

        $this->assertSame('READ-UNCOMMITTED', $value);
        $this->level->restorePreviousStatus();

        $value = $this->db->fetchOne('SELECT ' . $isolation);
        $this->assertSame('REPEATABLE-READ', $value);
    }

}
