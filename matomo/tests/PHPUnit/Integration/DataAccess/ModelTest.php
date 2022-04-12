<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\DataAccess;

use Piwik\Common;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\Date;
use Piwik\Db;
use Piwik\Period\Factory;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\DataAccess\Model;

/**
 * @group Core
 * @group DataAccess
 */
class ModelTest extends IntegrationTestCase
{
    /**
     * @var Model
     */
    private $model;
    private $tableName = 'archive_numeric_test';

    public function setUp(): void
    {
        parent::setUp();

        $this->model = new Model();
        $this->model->createArchiveTable($this->tableName, 'archive_numeric');
    }

    public function test_getInvalidatedArchiveIdsSafeToDelete_handlesCutOffGroupMaxLenCorrectly()
    {
        Db::get()->query('SET SESSION group_concat_max_len=32');

        $this->insertArchiveData([
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
            ['date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => ArchiveWriter::DONE_OK],
        ]);

        // sanity check
        $table = ArchiveTableCreator::getNumericTable(Date::factory('2020-02-03'));
        $sql = "SELECT GROUP_CONCAT(idarchive, '.', value ORDER BY ts_archived DESC) as archives
                  FROM `$table`
              GROUP BY idsite, date1, date2, period, name";
        $result = Db::fetchRow($sql);
        $this->assertEquals(['archives' => '21.1,20.1,19.1,18.1,17.1,16.1,15'], $result);

        $ids = $this->model->getInvalidatedArchiveIdsSafeToDelete($table, $setMaxLen = false);

        $expected = ['20', '19', '18', '17', '16'];
        $this->assertEquals($expected, $ids);
    }

    public function test_resetFailedArchivingJobs_updatesCorrectStatuses()
    {
        Date::$now = strtotime('2020-03-03 04:00:00');

        $this->insertInvalidations([
            ['idsite' => 1, 'date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done', 'value' => 1, 'status' => 1, 'ts_invalidated' => '2020-03-01 00:00:00', 'ts_started' => '2020-03-02 03:00:00'],
            ['idsite' => 2, 'date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'done.Plugin', 'value' => 2, 'status' => 0, 'ts_invalidated' => '2020-03-01 00:00:00', 'ts_started' => '2020-03-02 03:00:00'],
            ['idsite' => 1, 'date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'doneblablah', 'value' => 3, 'status' => 0, 'ts_invalidated' => '2020-03-01 00:00:00', 'ts_started' => '2020-03-03 00:00:00'],
            ['idsite' => 3, 'date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'donebluhbluh', 'value' => 4, 'status' => 1, 'ts_invalidated' => '2020-03-01 00:00:00', 'ts_started' => '2020-03-02 12:00:00'],
            ['idsite' => 1, 'date1' => '2020-02-03', 'date2' => '2020-02-03', 'period' => 1, 'name' => 'donedone', 'value' => 5, 'status' => 1, 'ts_invalidated' => '2020-03-01 00:00:00', 'ts_started' => '2020-03-01 03:00:00'],
        ]);

        $this->model->resetFailedArchivingJobs();

        $idinvalidationStatus = Db::fetchAll('SELECT idinvalidation, status FROM ' . Common::prefixTable('archive_invalidations'));

        $expected = [
            ['idinvalidation' => 1, 'status' => 0],
            ['idinvalidation' => 2, 'status' => 0],
            ['idinvalidation' => 3, 'status' => 0],
            ['idinvalidation' => 4, 'status' => 1],
            ['idinvalidation' => 5, 'status' => 0],
        ];

        $this->assertEquals($expected, $idinvalidationStatus);
    }

    public function test_insertNewArchiveId()
    {
        $this->assertAllocatedArchiveId(1);
        $this->assertAllocatedArchiveId(2);
        $this->assertAllocatedArchiveId(3);
        $this->assertAllocatedArchiveId(4);
        $this->assertAllocatedArchiveId(5);
        $this->assertAllocatedArchiveId(6);
    }

    private function assertAllocatedArchiveId($expectedId)
    {
        $id = $this->model->allocateNewArchiveId($this->tableName);

        $this->assertEquals($expectedId, $id);
    }

    /**
     * @dataProvider getTestDataForHasChildArchivesInPeriod
     */
    public function test_hasChildArchivesInPeriod_returnsFalseIfThereIsNoChildPeriod($archivesToInsert, $idSite, $date, $period, $expected)
    {
        $this->insertArchiveData($archivesToInsert);

        $periodObj = Factory::build($period, $date);
        $result = $this->model->hasChildArchivesInPeriod($idSite, $periodObj);
        $this->assertEquals($expected, $result);
    }

    public function test_hasInvalidationForPeriodAndName_returnsTrueIfExists()
    {
        $date = '2021-03-23';
        $this->insertInvalidations([
            ['date1' => $date, 'date2' => $date, 'period' => 1, 'name' => 'done'],
        ]);

        $periodObj = Factory::build('day', $date);
        $result = $this->model->hasInvalidationForPeriodAndName(1, $periodObj, 'done');
        $this->assertTrue($result);
    }

    public function test_hasInvalidationForPeriodAndName_returnsTrueIfExistsForReport()
    {
        $date = '2021-03-23';
        $this->insertInvalidations([
            ['date1' => $date, 'date2' => $date, 'period' => 1, 'name' => 'done', 'report' => 'myReport'],
        ]);

        $periodObj = Factory::build('day', $date);
        $result = $this->model->hasInvalidationForPeriodAndName(1, $periodObj, 'done', 'myReport');
        $this->assertTrue($result);
    }

    public function test_hasInvalidationForPeriodAndName_returnsFalseIfNotExistsForReport()
    {
        $date = '2021-03-23';
        $this->insertInvalidations([
            ['date1' => $date, 'date2' => $date, 'period' => 1, 'name' => 'done', 'report' => 'myReport'],
        ]);

        $periodObj = Factory::build('day', $date);
        $result = $this->model->hasInvalidationForPeriodAndName(1, $periodObj, 'done', 'otherReport');
        $this->assertFalse($result);
    }

    public function test_hasInvalidationForPeriodAndName_returnsFalseIfNotExists()
    {
        $date = '2021-03-23';
        $date2 = '2021-03-22';
        $this->insertInvalidations([
            ['date1' => $date, 'date2' => $date, 'period' => 1, 'name' => 'done'],
        ]);

        $periodObj = Factory::build('day', $date2);
        $result = $this->model->hasInvalidationForPeriodAndName(1, $periodObj, 'done');
        $this->assertFalse($result);
    }

    public function getTestDataForHasChildArchivesInPeriod()
    {
        return [
            // day period, no child
            [
                [
                    ['date1' => '2015-02-01', 'date2' => '2015-02-28', 'period' => 3, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-03',
                'day',
                false,
            ],

            // week period, no child
            [
                [
                    ['date1' => '2015-02-01', 'date2' => '2015-02-28', 'period' => 3, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-03',
                'week',
                false,
            ],

            // month period, no child
            [
                [
                    ['date1' => '2015-01-31', 'date2' => '2015-01-31', 'period' => 1, 'name' => 'done', 'value' => 1],
                    ['date1' => '2015-01-01', 'date2' => '2015-12-31', 'period' => 4, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-04',
                'month',
                false,
            ],

            // year period, no child
            [
                [],
                1,
                '2015-02-03',
                'year',
                false,
            ],

            // week period, w/ child
            [
                [
                    ['date1' => '2015-02-01', 'date2' => '2015-02-28', 'period' => 3, 'name' => 'done', 'value' => 1],
                    ['date1' => '2015-01-31', 'date2' => '2015-01-31', 'period' => 1, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-01',
                'week',
                true,
            ],
            [
                [
                    ['date1' => '2015-02-01', 'date2' => '2015-02-28', 'period' => 3, 'name' => 'done', 'value' => 1],
                    ['date1' => '2015-02-11', 'date2' => '2015-02-11', 'period' => 1, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-10',
                'week',
                true,
            ],

            // month period, w/ child
            [
                [
                    ['date1' => '2015-02-09', 'date2' => '2015-02-15', 'period' => 2, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-10',
                'month',
                true,
            ],
            [
                [
                    ['date1' => '2015-02-09', 'date2' => '2015-02-09', 'period' => 2, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-10',
                'month',
                true,
            ],
            [
                [
                    ['date1' => '2015-02-01', 'date2' => '2015-02-01', 'period' => 2, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-10',
                'month',
                true,
            ],

            // year period, w/ child
            [
                [
                    ['date1' => '2015-02-01', 'date2' => '2015-02-28', 'period' => 3, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-04',
                'year',
                true,
            ],
            [
                [
                    ['date1' => '2015-04-01', 'date2' => '2015-04-01', 'period' => 1, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-04',
                'year',
                true,
            ],
            [
                [
                    ['date1' => '2015-04-01', 'date2' => '2015-04-01', 'period' => 1, 'name' => 'done', 'value' => 4],
                ],
                1,
                '2015-02-04',
                'year',
                true,
            ],
            [
                [
                    ['date1' => '2015-04-01', 'date2' => '2015-04-01', 'period' => 1, 'name' => 'done', 'value' => 5],
                    ['date1' => '2014-04-01', 'date2' => '2014-04-01', 'period' => 1, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-02-04',
                'year',
                true,
            ],

            // range period w/ day child
            [
                [
                    ['date1' => '2015-04-01', 'date2' => '2015-04-01', 'period' => 1, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-03-30,2015-04-05',
                'range',
                true,
            ],
            [
                [
                    ['date1' => '2015-04-01', 'date2' => '2015-04-01', 'period' => 1, 'name' => 'done', 'value' => 1],
                ],
                1,
                '2015-04-01,2015-04-05',
                'range',
                true,
            ],
        ];
    }

    public function test_getNextInvalidatedArchive_returnsCorrectOrder()
    {
        $this->insertInvalidations([
            ['date1' => '2015-03-30', 'date2' => '2015-03-30', 'period' => 1, 'name' => 'done' . md5('testsegment8')],
            ['date1' => '2015-04-01', 'date2' => '2015-04-01', 'period' => 1, 'name' => 'done'],
            ['date1' => '2015-04-02', 'date2' => '2015-04-02', 'period' => 1, 'name' => 'done' . md5('testsegment1')],
            ['date1' => '2015-01-01', 'date2' => '2015-12-31', 'period' => 4, 'name' => 'done'],
            ['date1' => '2015-04-06', 'date2' => '2015-04-12', 'period' => 2, 'name' => 'done' . md5('testsegment3')],
            ['date1' => '2015-03-29', 'date2' => '2015-03-29', 'period' => 1, 'name' => 'done'],
            ['date1' => '2015-03-30', 'date2' => '2015-03-30', 'period' => 1, 'name' => 'done'],
            ['date1' => '2015-04-04', 'date2' => '2015-04-04', 'period' => 1, 'name' => 'done'],
            ['date1' => '2015-03-29', 'date2' => '2015-03-29', 'period' => 1, 'name' => 'done' . md5('testsegment2')],
            ['date1' => '2015-04-01', 'date2' => '2015-04-30', 'period' => 3, 'name' => 'done'],
            ['date1' => '2015-04-15', 'date2' => '2015-04-24', 'period' => 5, 'name' => 'done'],
            ['date1' => '2015-04-06', 'date2' => '2015-04-06', 'period' => 1, 'name' => 'done'],
            ['date1' => '2015-04-06', 'date2' => '2015-04-06', 'period' => 1, 'name' => 'done' . md5('testsegment3')],
            ['date1' => '2015-04-03', 'date2' => '2015-04-03', 'period' => 1, 'name' => 'done'],
            ['date1' => '2015-04-05', 'date2' => '2015-04-05', 'period' => 1, 'name' => 'done'],
            ['date1' => '2015-03-30', 'date2' => '2015-04-05', 'period' => 2, 'name' => 'done'],
            ['date1' => '2015-04-01', 'date2' => '2015-04-30', 'period' => 3, 'name' => 'done' . md5('testsegment1')],
            ['date1' => '2015-03-01', 'date2' => '2015-03-24', 'period' => 1, 'name' => 'done'],
            ['date1' => '2015-04-06', 'date2' => '2015-04-12', 'period' => 2, 'name' => 'done'],
            ['date1' => '2015-04-02', 'date2' => '2015-04-02', 'period' => 1, 'name' => 'done'],
            ['date1' => '2015-03-01', 'date2' => '2015-03-31', 'period' => 3, 'name' => 'done'],
            ['date1' => '2015-03-31', 'date2' => '2015-03-31', 'period' => 1, 'name' => 'done'],
        ]);

        $expected = array (
            array (
                'idinvalidation' => '11',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-15',
                'date2' => '2015-04-24',
                'period' => '5',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '12',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-06',
                'date2' => '2015-04-06',
                'period' => '1',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '13',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-06',
                'date2' => '2015-04-06',
                'period' => '1',
                'name' => 'done764644a7142bdcbedaab92f9dedef5e5',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '19',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-06',
                'date2' => '2015-04-12',
                'period' => '2',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '5',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-06',
                'date2' => '2015-04-12',
                'period' => '2',
                'name' => 'done764644a7142bdcbedaab92f9dedef5e5',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '15',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-05',
                'date2' => '2015-04-05',
                'period' => '1',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '8',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-04',
                'date2' => '2015-04-04',
                'period' => '1',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '14',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-03',
                'date2' => '2015-04-03',
                'period' => '1',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '20',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-02',
                'date2' => '2015-04-02',
                'period' => '1',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '3',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-02',
                'date2' => '2015-04-02',
                'period' => '1',
                'name' => 'done67564f109e3f4bba6b185a5343ff2bb0',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '2',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-01',
                'date2' => '2015-04-01',
                'period' => '1',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '10',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-01',
                'date2' => '2015-04-30',
                'period' => '3',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '17',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-04-01',
                'date2' => '2015-04-30',
                'period' => '3',
                'name' => 'done67564f109e3f4bba6b185a5343ff2bb0',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '22',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-03-31',
                'date2' => '2015-03-31',
                'period' => '1',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '7',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-03-30',
                'date2' => '2015-03-30',
                'period' => '1',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '1',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-03-30',
                'date2' => '2015-03-30',
                'period' => '1',
                'name' => 'done0bb102ea2ac682a578480dd184736607',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '16',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-03-30',
                'date2' => '2015-04-05',
                'period' => '2',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '6',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-03-29',
                'date2' => '2015-03-29',
                'period' => '1',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '9',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-03-29',
                'date2' => '2015-03-29',
                'period' => '1',
                'name' => 'doneb321434abb5a139c17dadf08c9d2e315',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '18',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-03-01',
                'date2' => '2015-03-24',
                'period' => '1',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '21',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-03-01',
                'date2' => '2015-03-31',
                'period' => '3',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
            array (
                'idinvalidation' => '4',
                'idarchive' => NULL,
                'idsite' => '1',
                'date1' => '2015-01-01',
                'date2' => '2015-12-31',
                'period' => '4',
                'name' => 'done',
                'report' => null,
                'ts_started' => null,
                'status' => 0,
            ),
        );

        $actual = $this->model->getNextInvalidatedArchive(1, '2030-01-01 00:00:00', null, $useLimit = false);
        foreach ($actual as &$item) {
            unset($item['ts_invalidated']);
        }

        $this->assertEquals($expected, $actual);
    }

    public function test_deleteInvalidationsForDeletedSites()
    {
        Fixture::createWebsite('2014-01-01 00:00:00');

        $this->insertInvalidations([
            ['idsite' => 1, 'date1' => '2014-02-03', 'date2' => '2014-02-03', 'period' => 1, 'name' => 'done'],
            ['idsite' => 2, 'date1' => '2014-02-01', 'date2' => '2014-02-28', 'period' => 2, 'name' => 'done'],
            ['idsite' => 2, 'date1' => '2014-02-01', 'date2' => '2014-02-01', 'period' => 1, 'name' => 'done'],
            ['idsite' => 3, 'date1' => '2014-02-01', 'date2' => '2014-02-01', 'period' => 1, 'name' => 'done'],
        ]);

        $this->model->deleteInvalidationsForDeletedSites();

        $invalidations = Db::fetchAll("SELECT idsite, idinvalidation FROM " . Common::prefixTable('archive_invalidations') .
            " ORDER BY idinvalidation ASC");
        $this->assertEquals([
            ['idsite' => 1, 'idinvalidation' => 1],
        ], $invalidations);
    }

    private function insertArchiveData($archivesToInsert)
    {
        $idarchive = 1;
        $now = Date::now()->getDatetime();
        foreach ($archivesToInsert as $archive) {
            $table = ArchiveTableCreator::getNumericTable(Date::factory($archive['date1']));
            $sql = "INSERT INTO `$table` (idarchive, idsite, date1, date2, period, `name`, `value`, ts_archived) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            Db::query($sql, [
                $idarchive, 1, $archive['date1'], $archive['date2'], $archive['period'], $archive['name'], $archive['value'],
                $archive['ts_archived'] ?? $now
            ]);

            ++$idarchive;
        }
    }

    private function insertInvalidations(array $invalidations)
    {
        $table = Common::prefixTable('archive_invalidations');
        $now = Date::now()->getDatetime();
        foreach ($invalidations as $invalidation) {
            $sql = "INSERT INTO `$table` (idsite, date1, date2, period, `name`, status, ts_invalidated, ts_started, report) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            Db::query($sql, [
                $invalidation['idsite'] ?? 1, $invalidation['date1'], $invalidation['date2'], $invalidation['period'], $invalidation['name'],
                $invalidation['status'] ?? 0, $invalidation['ts_invalidated'] ?? $now, $invalidation['ts_started'] ?? null, $invalidation['report'] ?? null,
            ]);
        }
    }
}
