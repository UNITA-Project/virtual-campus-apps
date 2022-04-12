<?php

namespace ls\tests;

/**
 * Check the JSON saved in database.
 * @since 2017-11-01
 * @group json
 */
class CheckDatabaseJsonValuesTest extends TestBaseClass
{
    /**
     * 
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();

        $db = \Yii::app()->getDb();

        $db->schemaCachingDuration = 0;
        // Load all tables of the application in the schema
        $db->schema->getTables();
        // clear the cache of all loaded tables
        $db->schema->refresh();
        $db->active = false;
        $db->active = true;
    }

    /**
     * Tear down fixtures.
     */
    public static function teardownAfterClass(): void
    {
        self::$testHelper->teardownDatabase('__test_check_database_json');
        self::$testHelper->teardownDatabase('__test_update_helper_258');
        self::$testHelper->teardownDatabase('__test_update_helper_337');
    }

    /**
     *
     * @throws \CException
     */
    public function testCreate()
    {
        $db = \Yii::app()->getDb();

        $config = require(\Yii::app()->getBasePath() . '/config/config.php');
        $version = require(\Yii::app()->getBasePath() . '/config/version.php');
        $connection = self::$testHelper->connectToNewDatabase('__test_check_database_json');
        $this->assertNotEmpty($connection, 'Could connect to new database');

        // Get InstallerController.
        $inst = new \InstallerConfigForm();
        $inst->db = \Yii::app()->db;
        $inst->dbtype = $inst->db->driverName;
        $result = $inst->setupTables();
        if ($result) {
            print_r($result);
        }

        // Run upgrade.
        $result = \db_upgrade_all($version['dbversionnumber']);

        // Check JSON.
        $this->checkMenuEntriesJson($inst->db);
        $this->checkTemplateConfigurationJson($inst->db);

        // Connect to old database.
        $db->setActive(false);
        \Yii::app()->setComponent('db', $config['components']['db'], false);
        $db->setActive(true);
    }

    /**
     * 
     */
    public function testUpdateFrom258()
    {
        $connection = self::$testHelper->updateDbFromVersion(258);

        // Check JSON.
        $this->checkMenuEntriesJson($connection);
        $this->checkTemplateConfigurationJson($connection);

        $db = \Yii::app()->getDb();
        $db->setActive(false);
        $config = require(\Yii::app()->getBasePath() . '/config/config.php');
        \Yii::app()->setComponent('db', $config['components']['db'], false);
        $db->setActive(true);
    }

    /**
     */
    public function testUpdateFrom337()
    {
        $connection = self::$testHelper->updateDbFromVersion(337);

        // Check JSON.
        $this->checkMenuEntriesJson($connection);
        $this->checkTemplateConfigurationJson($connection);

        $db = \Yii::app()->getDb();
        $db->setActive(false);
        $config = require(\Yii::app()->getBasePath() . '/config/config.php');
        \Yii::app()->setComponent('db', $config['components']['db'], false);
        $db->setActive(true);
    }

    /**
     * @param \CDbConnection $connection
     * @return void
     */
    protected function checkMenuEntriesJson(\CDbConnection $connection)
    {
        $data = $connection->createCommand('SELECT menu_title, data FROM {{surveymenu_entries}}')->query();
        foreach ($data as $field => $row) {
            $jsonString = $row['data'];
            if (!empty($jsonString)) {
                $json = json_decode($jsonString);
                $this->assertNotNull($json, $row['menu_title'] . ' ' . print_r($jsonString, true));
            } else {
                // Nothing to check.
            }
        }
    }

    /**
     * @param \CDbConnection $connection
     * @return void
     */
    protected function checkTemplateConfigurationJson(\CDbConnection $connection)
    {
        $data = $connection->createCommand(
            'SELECT
                files_css,
                files_js,
                files_print_css,
                options,
                cssframework_css,
                cssframework_js ,
                packages_to_load
            FROM
            {{template_configuration}}'
        )->query();
        foreach ($data as $field => $row) {
            foreach ($row as $field2 => $jsonString) {
                if (!empty($jsonString)) {
                    $json = json_decode($jsonString);
                    $this->assertNotNull(
                        $json,
                        'The following is not valid JSON: ' . $field2 . ': ' . print_r($jsonString, true)
                    );
                } else {
                    // Nothing to check.
                }

            }
        }
    }
}
