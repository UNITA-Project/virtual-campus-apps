<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Live\tests\Integration;

use Piwik\Common;
use Piwik\Config;
use Piwik\Date;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Plugins\Live\Exception\MaxExecutionTimeExceededException;
use Piwik\Plugins\Live\Model;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Tests\Integration\SegmentTest;

/**
 * @group Live
 * @group ModelTest
 * @group Plugins
 */
class ModelTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setSuperUser();
        Fixture::createWebsite('2010-01-01');
    }

    public function test_getStandAndEndDate_usesNowWhenDateOutOfRange()
    {
        $model = new Model();
        list($dateStart, $dateEnd) = $model->getStartAndEndDate($idSite = 1, 'year', '2025-01-01');

        $validDates = $this->getValidNowDates();

        $this->assertTrue(in_array($dateStart->getDatetime(), $validDates));
        $this->assertTrue(in_array($dateEnd->getDatetime(), $validDates));
        $this->assertNotEquals($dateStart->getDatetime(), $dateEnd->getDatetime());
    }

    public function test_getStandAndEndDate_usesNowWhenEndDateOutOfRange()
    {
        $model = new Model();
        list($dateStart, $dateEnd) = $model->getStartAndEndDate($idSite = 1, 'year', date('Y').'-01-01');

        $validDates = $this->getValidNowDates();

        $this->assertEquals(date('Y') . '-01-01 00:00:00', $dateStart->getDatetime());
        $this->assertTrue(in_array($dateEnd->getDatetime(), $validDates));
        $this->assertNotEquals($dateStart->getDatetime(), $dateEnd->getDatetime());
    }

    private function getValidNowDates()
    {
        $now = Date::now();
        $validDates = [$now->getDatetime()];
        $validDates[] = $now->subSeconds(1)->getDatetime();
        $validDates[] = $now->subSeconds(2)->getDatetime();
        $validDates[] = $now->addPeriod(1, 'second')->getDatetime();
        $validDates[] = $now->addPeriod(2, 'second')->getDatetime();

        return $validDates;
    }

    public function test_handleMaxExecutionTimeError_doesNotThrowExceptionWhenNotExceededTime()
    {
    	$db = Db::get();
    	$e = new \Exception('foo bar baz');
	    $sql = 'SELECT 1';
	    $bind = array();
	    $segment =  '';
	    $dateStart = Date::now()->subDay(1);
	    $dateEnd = Date::now();
	    $minTimestamp = 1;
	    $limit = 50;
        Model::handleMaxExecutionTimeError($db, $e, $segment, $dateStart, $dateEnd, $minTimestamp, $limit, [$sql, $bind]);
        $this->assertTrue(true);
    }

    public function test_handleMaxExecutionTimeError_whenTimeIsExceeded_noReasonFound()
    {
        $this->expectException(\Piwik\Plugins\Live\Exception\MaxExecutionTimeExceededException::class);
        $this->expectExceptionMessage('Live_QueryMaxExecutionTimeExceeded  Live_QueryMaxExecutionTimeExceededReasonUnknown');

        $db = Db::get();
    	$e = new \Exception('[3024] Query execution was interrupted, maximum statement execution time exceeded');
	    $sql = 'SELECT 1';
	    $bind = array();
	    $segment = '';
	    $dateStart = Date::now()->subDay(1);
	    $dateEnd = Date::now();
	    $minTimestamp = null;
	    $limit = 50;
        Model::handleMaxExecutionTimeError($db, $e, $segment, $dateStart, $dateEnd, $minTimestamp, $limit, [$sql, $bind]);
    }

    public function test_handleMaxExecutionTimeError_whenTimeIsExceeded_manyReasonsFound()
    {
        $this->expectException(\Piwik\Plugins\Live\Exception\MaxExecutionTimeExceededException::class);
        $this->expectExceptionMessage('Live_QueryMaxExecutionTimeExceeded  Live_QueryMaxExecutionTimeExceededReasonDateRange Live_QueryMaxExecutionTimeExceededReasonSegment Live_QueryMaxExecutionTimeExceededLimit');

        $db = Db::get();
    	$e = new \Exception('Query execution was interrupted, maximum statement execution time exceeded');
	    $segment = 'userId>=1';
	    $dateStart = Date::now()->subDay(10);
	    $dateEnd = Date::now();
	    $minTimestamp = null;
	    $limit = 5000;
        Model::handleMaxExecutionTimeError($db, $e, $segment, $dateStart, $dateEnd, $minTimestamp, $limit, ['param' => 'value']);
    }

    public function test_getLastMinutesCounterForQuery_maxExecutionTime()
    {
        if (SystemTestCase::isMysqli()) {
            $this->markTestSkipped('max_execution_time not supported on mysqli');
            return;
        }
        $this->expectException(MaxExecutionTimeExceededException::class);
        $this->expectExceptionMessage('Live_QueryMaxExecutionTimeExceeded');
        $this->setLowestMaxExecutionTime();

        $this->trackPageView();

        $model = new Model();
        $model->queryAndWhereSleepTestsOnly = true;
        $model->getNumVisits(1, 999999, '');
    }

    public function test_queryAdjacentVisitorId_maxExecutionTime()
    {
        if (SystemTestCase::isMysqli()) {
            $this->markTestSkipped('max_execution_time not supported on mysqli');
            return;
        }
        $this->expectException(MaxExecutionTimeExceededException::class);
        $this->expectExceptionMessage('Live_QueryMaxExecutionTimeExceeded');
        $this->setLowestMaxExecutionTime();

        $this->trackPageView();
        $model = new Model();
        $model->queryAndWhereSleepTestsOnly = true;
        $model->queryAdjacentVisitorId(1, '1234567812345678', Date::yesterday()->getDatetime(), '', true);
    }

    public function test_getStandAndEndDate()
    {
        $model = new Model();
        list($dateStart, $dateEnd) = $model->getStartAndEndDate($idSite = 1, 'year', '2018-02-01');

        $this->assertEquals('2018-01-01 00:00:00', $dateStart->getDatetime());
        $this->assertEquals('2019-01-01 00:00:00', $dateEnd->getDatetime());
    }

    public function test_isLookingAtMoreThanOneDay_whenNoDateSet()
    {
        $model = new Model();
        $this->assertTrue($model->isLookingAtMoreThanOneDay(null, null, null));
    }

    public function test_isLookingAtMoreThanOneDay_whenNoStartDateSet()
    {
        $model = new Model();
        $this->assertTrue($model->isLookingAtMoreThanOneDay(null, Date::now(), null));
    }

    public function test_isLookingAtMoreThanOneDay_whenNoStartDateSetAndMinTimestampIsOld()
    {
        $model = new Model();
        $this->assertTrue($model->isLookingAtMoreThanOneDay(null, Date::now(), Date::now()->subDay(5)->getTimestamp()));
    }

    public function test_isLookingAtMoreThanOneDay_whenNoStartDateSetButMinTimestampIsRecent()
    {
        $model = new Model();
        $this->assertFalse($model->isLookingAtMoreThanOneDay(null, Date::now(), Date::now()->subHour(5)->getTimestamp()));
    }

    public function test_isLookingAtMoreThanOneDay_whenNoEndDateIsSet_StartDateIsOld()
    {
        $model = new Model();
        $this->assertTrue($model->isLookingAtMoreThanOneDay(Date::now()->subDay(5), null, null));
    }

    public function test_isLookingAtMoreThanOneDay_whenNoEndDateIsSet_StartDateIsRecent()
    {
        $model = new Model();
        $this->assertFalse($model->isLookingAtMoreThanOneDay(Date::now()->subHour(5), null, null));
    }

    public function test_isLookingAtMoreThanOneDay_whenStartAndEndDateIsSet_onlyOneDay()
    {
        $model = new Model();
        $this->assertFalse($model->isLookingAtMoreThanOneDay(Date::yesterday()->subDay(1), Date::yesterday(), null));
    }

    public function test_isLookingAtMoreThanOneDay_whenStartAndEndDateIsSet_moreThanOneDay()
    {
        $model = new Model();
        $this->assertTrue($model->isLookingAtMoreThanOneDay(Date::yesterday()->subDay(2), Date::yesterday(), null));
    }

    public function test_makeLogVisitsQueryString()
    {
        $model = new Model();
        list($dateStart, $dateEnd) = $model->getStartAndEndDate($idSite = 1, 'month', '2010-01-01');
        list($sql, $bind) = $model->makeLogVisitsQueryString(
                $idSite = 1,
                $dateStart,
                $dateEnd,
                $segment = false,
                $offset = 0,
                $limit = 100,
                $visitorId = false,
                $minTimestamp = false,
                $filterSortOrder = false
        );
        $expectedSql = ' SELECT log_visit.*
                    FROM ' . Common::prefixTable('log_visit') . ' AS log_visit
                    WHERE log_visit.idsite in (?)
                      AND log_visit.visit_last_action_time >= ?
                      AND log_visit.visit_last_action_time <= ?
                    ORDER BY log_visit.idsite DESC, log_visit.visit_last_action_time DESC
                    LIMIT 0, 100';
        $expectedBind = array(
            '1',
            '2010-01-01 00:00:00',
            '2010-02-01 00:00:00',
        );
        $this->assertEquals(SegmentTest::removeExtraWhiteSpaces($expectedSql), SegmentTest::removeExtraWhiteSpaces($sql));
        $this->assertEquals(SegmentTest::removeExtraWhiteSpaces($expectedBind), SegmentTest::removeExtraWhiteSpaces($bind));
    }

    public function test_makeLogVisitsQueryString_withMultipleIdSites()
    {
        Piwik::addAction('Live.API.getIdSitesString', function (&$idSites) {
            $idSites = array(2,3,4);
        });

        $model = new Model();
        list($dateStart, $dateEnd) = $model->getStartAndEndDate($idSite = 1, 'month', '2010-01-01');
        list($sql, $bind) = $model->makeLogVisitsQueryString(
                $idSite = 1,
                $dateStart,
                $dateEnd,
                $segment = false,
                $offset = 0,
                $limit = 100,
                $visitorId = false,
                $minTimestamp = false,
                $filterSortOrder = false
        );
        $expectedSql = ' SELECT log_visit.*
                    FROM ' . Common::prefixTable('log_visit') . ' AS log_visit
                    WHERE log_visit.idsite in (?,?,?)
                      AND log_visit.visit_last_action_time >= ?
                      AND log_visit.visit_last_action_time <= ?
                    ORDER BY log_visit.visit_last_action_time DESC
                    LIMIT 0, 100';
        $expectedBind = array(
            '2',
            '3',
            '4',
            '2010-01-01 00:00:00',
            '2010-02-01 00:00:00',
        );
        $this->assertEquals(SegmentTest::removeExtraWhiteSpaces($expectedSql), SegmentTest::removeExtraWhiteSpaces($sql));
        $this->assertEquals(SegmentTest::removeExtraWhiteSpaces($expectedBind), SegmentTest::removeExtraWhiteSpaces($bind));
    }

    public function test_makeLogVisitsQueryStringWithOffset()
    {
        $model = new Model();

        list($dateStart, $dateEnd) = $model->getStartAndEndDate($idSite = 1, 'month', '2010-01-01');
        list($sql, $bind) = $model->makeLogVisitsQueryString(
                $idSite = 1,
                $dateStart,
                $dateEnd,
                $segment = false,
                $offset = 15,
                $limit = 100,
                $visitorId = false,
                $minTimestamp = false,
                $filterSortOrder = false
        );
        $expectedSql = ' SELECT log_visit.*
                    FROM ' . Common::prefixTable('log_visit') . ' AS log_visit
                    WHERE log_visit.idsite in (?)
                      AND log_visit.visit_last_action_time >= ?
                      AND log_visit.visit_last_action_time <= ?
                    ORDER BY log_visit.idsite DESC, log_visit.visit_last_action_time DESC
                    LIMIT 15, 100';
        $expectedBind = array(
            '1',
            '2010-01-01 00:00:00',
            '2010-02-01 00:00:00',
        );
        $this->assertEquals(SegmentTest::removeExtraWhiteSpaces($expectedSql), SegmentTest::removeExtraWhiteSpaces($sql));
        $this->assertEquals(SegmentTest::removeExtraWhiteSpaces($expectedBind), SegmentTest::removeExtraWhiteSpaces($bind));
    }


    public function test_makeLogVisitsQueryString_whenSegment()
    {
        $model = new Model();
        list($dateStart, $dateEnd) = $model->getStartAndEndDate($idSite = 1, 'month', '2010-01-01');
        list($sql, $bind) = $model->makeLogVisitsQueryString(
            $idSite = 1,
            $dateStart,
            $dateEnd,
            $segment = 'siteSearchCategory==Test',
            $offset = 10,
            $limit = 100,
            $visitorId = 'abc',
            $minTimestamp = false,
            $filterSortOrder = false
        );
        $expectedSql = ' SELECT log_visit.* 
                        FROM log_visit AS log_visit 
                        LEFT JOIN log_link_visit_action AS log_link_visit_action ON log_link_visit_action.idvisit = log_visit.idvisit 
                        WHERE ( 
                            log_visit.idsite in (?) 
                            AND log_visit.idvisitor = ? 
                            AND log_visit.visit_last_action_time >= ? 
                            AND log_visit.visit_last_action_time <= ? ) 
                            AND ( log_link_visit_action.search_cat = ? ) 
                        GROUP BY log_visit.idvisit 
                        ORDER BY log_visit.idsite DESC, log_visit.visit_last_action_time DESC
                         LIMIT 10, 100';
        $expectedBind = array(
            '1',
            Common::hex2bin('abc'),
            '2010-01-01 00:00:00',
            '2010-02-01 00:00:00',
            'Test',
        );
        $this->assertEquals(SegmentTest::removeExtraWhiteSpaces($expectedSql), SegmentTest::removeExtraWhiteSpaces($sql));
        $this->assertEquals(SegmentTest::removeExtraWhiteSpaces($expectedBind), SegmentTest::removeExtraWhiteSpaces($bind));
    }

    public function test_makeLogVisitsQueryString_addsMaxExecutionHintIfConfigured()
    {
        $this->setMaxExecutionTime(30);

        $model = new Model();
        list($dateStart, $dateEnd) = $model->getStartAndEndDate($idSite = 1, 'month', '2010-01-01');
        list($sql, $bind) = $model->makeLogVisitsQueryString(
            $idSite = 1,
            $dateStart,
            $dateEnd,
            $segment = false,
            $offset = 0,
            $limit = 100,
            $visitorId = false,
            $minTimestamp = false,
            $filterSortOrder = false
        );
        $expectedSql = 'SELECT  /*+ MAX_EXECUTION_TIME(30000) */ 
				log_visit.*';

        $this->setMaxExecutionTime(-1);

        $this->assertStringStartsWith($expectedSql, trim($sql));
    }

    public function test_makeLogVisitsQueryString_doesNotAddsMaxExecutionHintForVisitorIds()
    {
        $this->setMaxExecutionTime(30);

        $model = new Model();
        list($dateStart, $dateEnd) = $model->getStartAndEndDate($idSite = 1, 'month', '2010-01-01');
        list($sql, $bind) = $model->makeLogVisitsQueryString(
            $idSite = 1,
            $dateStart,
            $dateEnd,
            $segment = false,
            $offset = 0,
            $limit = 100,
            $visitorId = '1234567812345678',
            $minTimestamp = false,
            $filterSortOrder = false
        );
        $expectedSql = 'SELECT
				log_visit.*';

        $this->setMaxExecutionTime(-1);

        $this->assertStringStartsWith($expectedSql, trim($sql));
    }

    public function test_splitDatesIntoMultipleQueries_notMoreThanADayUsesOnlyOneQuery()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2010-01-02 00:00:00', $limit = 5, $offset = 0);

        $this->assertEquals(array('2010-01-01 00:00:00 2010-01-02 00:00:00'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_notMoreThanADayUsesOnlyOneQueryDesc()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2010-01-02 00:00:00', $limit = 5, $offset = 0, 'asc');

        $this->assertEquals(array('2010-01-01 00:00:00 2010-01-02 00:00:00'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanADayLessThanAWeek()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2010-01-02 00:01:00', $limit = 5, $offset = 0);

        $this->assertEquals(array('2010-01-01 00:01:00 2010-01-02 00:01:00', '2010-01-01 00:00:00 2010-01-01 00:00:59'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanADayLessThanAWeekAsc()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2010-01-02 00:01:00', $limit = 5, $offset = 0, 'asc');

        $this->assertEquals(array('2010-01-01 00:00:00 2010-01-01 23:59:59', '2010-01-02 00:00:00 2010-01-02 00:01:00'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanAWeekLessThanMonth()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2010-01-20 04:01:00', $limit = 5, $offset = 0);

        $this->assertEquals(array('2010-01-19 04:01:00 2010-01-20 04:01:00', '2010-01-12 04:01:00 2010-01-19 04:00:59', '2010-01-01 00:00:00 2010-01-12 04:00:59'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanAWeekLessThanMonthAsc()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2010-01-20 04:01:00', $limit = 5, $offset = 0, 'asc');

        $this->assertEquals(array('2010-01-01 00:00:00 2010-01-01 23:59:59', '2010-01-02 00:00:00 2010-01-08 23:59:59', '2010-01-09 00:00:00 2010-01-20 04:01:00'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanMonthLessThanYear()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2010-02-20 04:01:00', $limit = 5, $offset = 0);

        $this->assertEquals(array('2010-02-19 04:01:00 2010-02-20 04:01:00', '2010-02-12 04:01:00 2010-02-19 04:00:59', '2010-01-13 04:01:00 2010-02-12 04:00:59', '2010-01-01 00:00:00 2010-01-13 04:00:59'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanMonthLessThanYearAsc()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2010-02-20 04:01:00', $limit = 5, $offset = 0, 'asc');

        $this->assertEquals(array('2010-01-01 00:00:00 2010-01-01 23:59:59', '2010-01-02 00:00:00 2010-01-08 23:59:59', '2010-01-09 00:00:00 2010-02-07 23:59:59', '2010-02-08 00:00:00 2010-02-20 04:01:00'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanYear()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2012-02-20 04:01:00', $limit = 5, $offset = 0);

        $this->assertEquals(array('2012-02-19 04:01:00 2012-02-20 04:01:00', '2012-02-12 04:01:00 2012-02-19 04:00:59', '2012-01-13 04:01:00 2012-02-12 04:00:59', '2011-01-01 04:01:00 2012-01-13 04:00:59', '2010-01-01 00:00:00 2011-01-01 04:00:59'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanYearAsc()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2012-02-20 04:01:00', $limit = 5, $offset = 0, 'asc');

        $this->assertEquals(array('2010-01-01 00:00:00 2010-01-01 23:59:59', '2010-01-02 00:00:00 2010-01-08 23:59:59', '2010-01-09 00:00:00 2010-02-07 23:59:59', '2010-02-08 00:00:00 2011-02-07 23:59:59', '2011-02-08 00:00:00 2012-02-20 04:01:00'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanYear_withOffsetUsesLessQueries()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2012-02-20 04:01:00', $limit = 5, $offset = 5);

        $this->assertEquals(array('2012-02-19 04:01:00 2012-02-20 04:01:00', '2012-02-12 04:01:00 2012-02-19 04:00:59', '2010-01-01 00:00:00 2012-02-12 04:00:59'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanYear_withOffsetUsesLessQueriesAsc()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 04:01:00', '2012-02-20 00:00:00', $limit = 5, $offset = 5, 'asc');

        $this->assertEquals(array('2010-01-01 04:01:00 2010-01-02 04:00:59', '2010-01-02 04:01:00 2010-01-09 04:00:59', '2010-01-09 04:01:00 2012-02-20 00:00:00'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanYear_noLimitDoesntUseMultipleQueries()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 00:00:00', '2012-02-20 04:01:00', $limit = 0, $offset = 0);

        $this->assertEquals(array('2010-01-01 00:00:00 2012-02-20 04:01:00'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_moreThanYear_noLimitDoesntUseMultipleQueriesAsc()
    {
        $dates = $this->splitDatesIntoMultipleQueries('2010-01-01 04:01:00', '2012-02-20 00:00:00', $limit = 0, $offset = 0, 'asc');

        $this->assertEquals(array('2010-01-01 04:01:00 2012-02-20 00:00:00'), $dates);
    }

    public function test_splitDatesIntoMultipleQueries_noStartDate()
    {
        $dates = $this->splitDatesIntoMultipleQueries(false, '2012-02-20 04:01:00', $limit = 5, $offset = 0);

        $this->assertEquals(array('2012-02-19 04:01:00 2012-02-20 04:01:00', '2012-02-12 04:01:00 2012-02-19 04:00:59', '2012-01-13 04:01:00 2012-02-12 04:00:59', '2011-01-01 04:01:00 2012-01-13 04:00:59', ' 2011-01-01 04:00:59'), $dates);
    }

    private function splitDatesIntoMultipleQueries($startDate, $endDate, $limit, $offset, $order='desc')
    {
        if ($startDate) {
            $startDate = Date::factory($startDate);
        }
        if ($endDate) {
            $endDate = Date::factory($endDate);
        }
        $model = new Model();
        $queries = $model->splitDatesIntoMultipleQueries($startDate, $endDate, $limit, $offset, $order);

        return array_map(function ($query) { return ($query[0] ? $query[0]->getDatetime() : '') . ' ' . ($query[1] ? $query[1]->getDatetime() : ''); }, $queries);
    }

    protected function setSuperUser()
    {
        FakeAccess::$superUser = true;
    }

    public function provideContainerConfig()
    {
        return array(
            'Piwik\Access' => new FakeAccess()
        );
    }

    private function setLowestMaxExecutionTime(): void
    {
        $this->setMaxExecutionTime(0.001);
    }

    private function setMaxExecutionTime($time): void
    {
        $config = Config::getInstance();
        $general = $config->General;
        $general['live_query_max_execution_time'] = $time;
        $config->General = $general;
    }

    private function trackPageView(): void
    {
        // Needed for the tests that may execute a sleep() to test max execution time. Otherwise if the table is empty
        // the sleep would not be executed making the tests fail randomly
        $t = Fixture::getTracker(1, Date::now()->getDatetime(), $defaultInit = true);
        $t->setTokenAuth(Fixture::getTokenAuth());
        $t->setVisitorId(substr(sha1('X4F66G776HGI'), 0, 16));
        $t->doTrackPageView('foo');
    }
}
