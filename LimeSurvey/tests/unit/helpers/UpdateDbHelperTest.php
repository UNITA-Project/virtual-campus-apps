<?php

namespace ls\tests;

/**
 * @since 2017-06-16
 * @group dbhelper
 * @group db
 */
class UpdateDbHelperTest extends TestBaseClass
{
    /**
     * Tear down fixtures.
     */
    public static function tearDownAfterClass(): void
    {
        self::$testHelper->teardownDatabase('__test_update_helper_153');
        self::$testHelper->teardownDatabase('__test_update_helper_258');
        self::$testHelper->teardownDatabase('__test_update_helper_337');
        self::$testHelper->teardownDatabase('__test_install_script');
        self::$testHelper->teardownDatabase('__test_install_script_compare');
    }

    /**
     * Run the database PHP install script.
     * @group install
     * @throws \CException
     */
    public function testInstallPhp()
    {
        $db = \Yii::app()->getDb();

        $config = require(\Yii::app()->getBasePath() . '/config/config.php');
        $connection = self::$testHelper->connectToNewDatabase('__test_install_script');
        $this->assertNotEmpty($connection, 'Could connect to new database');

        // Get InstallerController.
        $inst = new \InstallerConfigForm();
        $inst->db = \Yii::app()->db;
        $inst->dbtype = $inst->db->driverName;
        $result = $inst->setupTables();
        if ($result) {
            print_r($result);
        }

        // Dump database to file.
        /*
        $output = array();
        $result = exec(
            sprintf(
                'mysqldump -u %s -p%s __test_install_script > tests/data/tmp/__test_install_script-dump.sql',
                $config['components']['db']['username'],
                $config['components']['db']['password']
            ),
            $output
        );
        $this->assertEmpty($output, 'No output from mysqldump');
        $this->assertEmpty($result, 'No last line output from mysqldump');
         */

        // Connect to old database.
        $db->setActive(false);
        \Yii::app()->setComponent('db', $config['components']['db'], false);
        $db->setActive(true);
    }

    /**
     * Run db_upgrade_all() from dbversion 153.
     */
    public function testUpdateFrom153()
    {
        self::$testHelper->updateDbFromVersion(153);

        $db = \Yii::app()->getDb();
        $config = require(\Yii::app()->getBasePath() . '/config/config.php');

        // Connect to old database.
        \Yii::app()->setComponent('db', $config['components']['db'], false);
        $db->setActive(true);
    }

    /**
     * Run db_upgrade_all() from dbversion 258, to make sure
     * there are no conflicts or syntax errors.
     * @group upgradeall
     * @throws \CException
     */
    public function testDbUpgradeFrom258()
    {
        self::$testHelper->updateDbFromVersion(258);

        $db = \Yii::app()->getDb();
        $config = require(\Yii::app()->getBasePath() . '/config/config.php');

        // Dump database to file.
        /*
        $output = array();
        $result = exec(
            sprintf(
                'mysqldump -u %s -p%s __test_update_helper_258 > tests/data/tmp/__test_update_helper_258-dump.sql',
                $config['components']['db']['username'],
                $config['components']['db']['password']
            ),
            $output
        );
        $this->assertEmpty($output, 'No output from mysqldump');
        $this->assertEmpty($result, 'No last line output from mysqldump');
         */

        // Connect to old database.
        \Yii::app()->setComponent('db', $config['components']['db'], false);
        $db->setActive(true);

        // Database is deleted in teardownAfterClass().
    }

    /**
     * @group from337
     * @throws \CException
     */
    public function testDbUpgradeFrom337()
    {
        self::$testHelper->updateDbFromVersion(337);

        $db = \Yii::app()->getDb();
        $config = require(\Yii::app()->getBasePath() . '/config/config.php');

        // Connect to old database.
        \Yii::app()->setComponent('db', $config['components']['db'], false);
        $db->setActive(true);
    }

    /**
     * Compare database between upgrade and fresh install.
     * @group dbcompare
     * @throws \CException
     */
    public function testCompareUpgradeAndFreshInstall()
    {
        //$connection = self::$testHelper->updateDbFromVersion(153);
        //$upgradeTables = $connection->schema->getTables();
        //$this->compareAux($upgradeTables, 153);

        $connection = self::$testHelper->updateDbFromVersion(258);
        $upgradeTables = $connection->schema->getTables();
        $this->compareAux($upgradeTables, 258);

        $connection = self::$testHelper->updateDbFromVersion(337);
        $upgradeTables = $connection->schema->getTables();
        $this->compareAux($upgradeTables, 337);
    }

    /**
     * @param array $upgradeTables
     * @return void
     * @throws \CException
     */
    protected function compareAux(array $upgradeTables, $upgradedFrom)
    {
        $config = require(\Yii::app()->getBasePath() . '/config/config.php');

        $dbo = \Yii::app()->getDb();

        /*
        $config = require(\Yii::app()->getBasePath() . '/config/config.php');
        // Get database name.
        preg_match("/dbname=([^;]*)/", \Yii::app()->db->connectionString, $matches);
        $this->assertEquals(2, count($matches));
        $oldDatabase = $matches[1];
        $newConfig = $config;
        $newConfig['components']['db']['connectionString'] = str_replace(
            'dbname=' . $oldDatabase,
            'dbname=' . '__test_install_script_compare',
            $config['components']['db']['connectionString']
        );
        $connection= new \DbConnection(
            $newConfig['components']['db']['connectionString'],
            'root',
            ''
        );
        $connection->active = true;
         */

        \Yii::app()->cache->flush();

        self::$testHelper->teardownDatabase('__test_install_script_compare');
        $connection = self::$testHelper->connectToNewDatabase('__test_install_script_compare');
        $this->assertNotEmpty($connection, 'Could not connect to new database: ' . json_encode($connection));
        $connection->schemaCachingDuration = 0; // Deactivate schema caching
        $connection->schema->refresh();

        // Get InstallerController.
        $inst = new \InstallerConfigForm();
        $inst->db = \Yii::app()->db;
        $inst->dbtype = $inst->db->driverName;
        try {
            $result = $inst->setupTables();
        } catch (\CHttpException $ex) {
            $this->assertTrue(
                false,
                $ex->getMessage()
            );
        }
        if ($result) {
            print_r($result);
        }
        $inst->db->schema->refresh();
        $freshInstallTables = $inst->db->schema->getTables();

        $this->assertEquals(count($upgradeTables), count($freshInstallTables), 'Same number of tables');
        $this->assertEquals(array_keys($upgradeTables), array_keys($freshInstallTables), 'Same number of tables');

        // Loop tables.
        $upgradeKeys = array_keys($upgradeTables);
        $freshInstallKeys = array_keys($freshInstallTables);
        for ($i = 0; $i < count(array_keys($upgradeTables)); $i++) {
            $this->assertEquals($upgradeKeys[$i], $freshInstallKeys[$i]);
            $upgradeTable = $upgradeTables[$upgradeKeys[$i]];
            $freshTable = $freshInstallTables[$freshInstallKeys[$i]];

            $upgradeColumns = $upgradeTable->columns;
            $freshColumns = $freshTable->columns;

            // Loop columns.
            foreach ($upgradeColumns as $columnName => $upgradeColumn) {
                $upgradeColumn = (array) $upgradeColumn;
                $freshColumn = (array) $freshColumns[$columnName];
                // Loop fields in column.
                foreach ($upgradeColumn as $fieldName => $field) {
                    $this->assertEquals(
                        $field,
                        $freshColumn[$fieldName],
                        sprintf(
                            '(Upgraded from db version %d) Comparing field name "%s" for column "%s" in table "%s": '
                            .' upgraded value: %s;  fresh install value: %s',
                            $upgradedFrom,
                            $fieldName,
                            $columnName,
                            $upgradeKeys[$i],
                            json_encode($field),
                            json_encode($freshColumn[$fieldName])
                        )
                    );
                }
            }
        }

        /* Code to dump diff, but nearly useless due to collate difference.
        $output = array();
        exec(
            sprintf(
                'mysqldump -u %s -p%s __test_update_helper_%d > tests/tmp/__test_update_helper_%d-dump.sql',
                $config['components']['db']['username'],
                $config['components']['db']['password'],
                $upgradedFrom,
                $upgradedFrom
            ),
            $output
        );
         */

        // Connect to old database.
        $dbo->setActive(false);
        \Yii::app()->setComponent('db', $config['components']['db'], false);
        $dbo->setActive(true);
    }
}
