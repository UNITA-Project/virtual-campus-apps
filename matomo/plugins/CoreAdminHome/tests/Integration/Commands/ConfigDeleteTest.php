<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CoreAdminHome\tests\Integration\Commands;

use Psr\Container\ContainerInterface;
use Piwik\Application\Kernel\GlobalSettingsProvider;
use Piwik\Config;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

/**
 * @group Core
 * @group CoreAdminHome
 * @group Integration
 */
class ConfigDeleteTest extends ConsoleCommandTestCase
{
    /*
     * The text config:delete outputs when no matching value is found.
     */

    private const COMMAND = 'config:delete';
    private const CLASS_NAME_SHORT = 'ConfigDeleteTest';
    private const MSG_NOTHING_FOUND = 'Nothing found';

    /*
     * Path to store the test config file. It should be deleted when done.
     */
    private const TEST_CONFIG_PATH = '/tmp/test.config.ini.php';
    // Section 1.
    private const TEST_SECTION_1_NAME = self::CLASS_NAME_SHORT . '_test_section_1';
    private const TEST_SETTING_1_1_NAME = self::CLASS_NAME_SHORT . '_test_setting_1';
    private const TEST_SETTING_1_1_VALUE = self::CLASS_NAME_SHORT . '_test_value_1';
    private const TEST_SETTING_1_2_NAME = self::CLASS_NAME_SHORT . '_test_setting_2';
    private const TEST_SETTING_1_2_VALUE = self::CLASS_NAME_SHORT . '_test_value_2';
    // Section 2.
    private const TEST_SECTION_2_NAME = self::CLASS_NAME_SHORT . '_test_section_2';
    private const TEST_SETTING_2_1_NAME = self::CLASS_NAME_SHORT . '_array_setting';
    private const TEST_SETTING_2_1_VALUE_0 = self::CLASS_NAME_SHORT . '_arr_val_1';
    private const TEST_SETTING_2_1_VALUE_1 = self::CLASS_NAME_SHORT . '_arr_val_2';
    private const TEST_SETTING_2_1_VALUE_2 = self::CLASS_NAME_SHORT . '_arr_val_3';
    private const TEST_SETTING_2_1_VALUES = [self::TEST_SETTING_2_1_VALUE_0, self::TEST_SETTING_2_1_VALUE_1, self::TEST_SETTING_2_1_VALUE_2];

    public static function setUpBeforeClass(): void
    {
        self::removeTestConfigFile();
        parent::setUpBeforeClass();
    }

    public function setUp(): void
    {
        self::removeTestConfigFile();
        parent::setUp();
    }

    private static function getTestConfigFilePath()
    {
        return PIWIK_INCLUDE_PATH . self::TEST_CONFIG_PATH;
    }

    public static function provideContainerConfigBeforeClass()
    {
        return array(
            // use a config instance that will save to a test INI file
            'Piwik\Config' => function (ContainerInterface $containerInterface) {
                /** @var GlobalSettingsProvider $actualGlobalSettingsProvider */
                $actualGlobalSettingsProvider = $containerInterface->get('Piwik\Application\Kernel\GlobalSettingsProvider');

                $config = self::makeTestConfig();

                // copy over sections required for tests
                $config->tests = $actualGlobalSettingsProvider->getSection('tests');
                $config->database = $actualGlobalSettingsProvider->getSection('database_tests');

                return $config;
            },
        );
    }

    private static function makeTestConfig()
    {
        $settingsProvider = new GlobalSettingsProvider(null, self::getTestConfigFilePath());
        $config = new Config($settingsProvider);

        // Add the first section.
        $sectionName = self::TEST_SECTION_1_NAME;
        $config->$sectionName[self::TEST_SETTING_1_1_NAME] = self::TEST_SETTING_1_1_VALUE;
        $config->$sectionName[self::TEST_SETTING_1_2_NAME] = self::TEST_SETTING_1_2_VALUE;

        // Add a second section so we are testing that we do not accidentally return it.
        $sectionName = self::TEST_SECTION_1_NAME . '_second_section';
        // Add a setting with the same name as in section #1 but with a random int value.
        $config->$sectionName[self::TEST_SETTING_1_1_NAME] = random_int(PHP_INT_MIN, PHP_INT_MAX);
        // Add another setting to the same section with some bogus content.
        $config->$sectionName[self::TEST_SETTING_1_2_NAME . '_another'] = '127.0.0.1';

        // Add an array value like section=PluginsInstalled; setting=PluginsInstalled[].
        $sectionName = self::TEST_SECTION_2_NAME;
        // Add some values to the setting array.
        $config->$sectionName[self::TEST_SETTING_2_1_NAME] = self::TEST_SETTING_2_1_VALUES;

        $config->forceSave();
        return $config;
    }

    private static function removeTestConfigFile()
    {
        $configPath = self::getTestConfigFilePath();
        if (file_exists($configPath)) {
            unlink($configPath);
        }
    }

    private static function makeNewConfig()
    {
        $settings = new GlobalSettingsProvider(null, self::getTestConfigFilePath());
        return new Config($settings);
    }

    private function runCommandWithOptions(string $sectionName, string $settingName, string $value = ''): object
    {

        $inputArr = [
            'command' => self::COMMAND,
            '--section' => $sectionName,
            '--key' => $settingName,
            '-vvv' => false,
        ];
        if (!empty($value)) {
            $inputArr['--value'] = $value;
        }
        $exitCode = $this->applicationTester->run($inputArr);

        // Pass true to getDisplay(true) to normalize line endings, then trim() bc CLI adds an \ automatically.
        $output = trim($this->applicationTester->getDisplay(true));

        // Put the results in an easy-to-handle object format.
        return (object) ['exitCode' => $exitCode, 'output' => $output];
    }

    private function runCommandWithArguments(string $sectionName, string $settingName = '', string $value = ''): object
    {

        $inputArr = [
            'command' => self::COMMAND,
            '-vvv' => false,
            'argument' => $sectionName . '.' . $settingName . (empty($value) ? '' : ".$value"),
        ];

        $exitCode = $this->applicationTester->run($inputArr);

        // Pass true to getDisplay(true) to normalize line endings, then trim() bc CLI adds an \ automatically.
        $output = trim($this->applicationTester->getDisplay(true));

        // Put the results in an easy-to-handle object format.
        return (object) ['exitCode' => $exitCode, 'output' => $output];
    }

    //
    //*************************************************************************
    // Tests that should yield errors.
    //*************************************************************************
    //
    public function testNoArgsShouldYieldError()
    {

        $inputArr = [
            'command' => 'config:get',
            '-vvv' => false,
        ];
        $exitCode = $this->applicationTester->run($inputArr);

        // The CLI error code should be >0 indicating failure.
        $this->assertGreaterThan(0, $exitCode);

        // Pass true to getDisplay(true) to normalize line endings, then trim() bc CLI adds an \ automatically.
        $output = trim($this->applicationTester->getDisplay(true));

        $this->assertStringContainsString('InvalidArgumentException', $output);
    }

    public function testEmptyArgsShouldYieldError()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithArguments('');

        // The CLI error code should be >0 indicating failure.
        $this->assertGreaterThan(0, $resultObj->exitCode);

        $this->assertStringContainsString('InvalidArgumentException', $resultObj->output);
    }

    public function testEmptyOptionsShouldYieldError()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithOptions('', '');

        // The CLI error code should be >0 indicating failure.
        $this->assertGreaterThan(0, $resultObj->exitCode);

        $this->assertStringContainsString('InvalidArgumentException', $resultObj->output);
    }

    public function testSetArgsAndOptionsShouldYieldError()
    {
        $inputArr = [
            'command' => 'config:get',
            'argument' => self::TEST_SECTION_1_NAME . '.' . self::TEST_SETTING_1_1_NAME,
            '--section' => self::TEST_SECTION_1_NAME,
            '--key' => self::TEST_SETTING_1_1_NAME,
            '-vvv' => false,
        ];
        $exitCode = $this->applicationTester->run($inputArr);

        // The CLI error code should be >0 indicating failure.
        $this->assertGreaterThan(0, $exitCode);

        // Pass true to getDisplay(true) to normalize line endings, then trim() bc CLI adds an \ automatically.
        $output = trim($this->applicationTester->getDisplay(true));

        $this->assertStringContainsString('InvalidArgumentException', $output);
    }

    public function testEmptySectionShouldYieldError()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithOptions('', self::TEST_SETTING_1_1_NAME);

        // The CLI error code should be >0 indicating failure.
        $this->assertGreaterThan(0, $resultObj->exitCode);

        $this->assertStringContainsString('InvalidArgumentException', $resultObj->output);
    }

    public function testScalarSettingWithArrayValShouldYieldError()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithOptions(self::TEST_SECTION_1_NAME, self::TEST_SETTING_1_1_NAME, self::CLASS_NAME_SHORT . '_Array_key_does_not_exist');

        // The CLI error code should be >0 indicating failure.
        $this->assertGreaterThan(0, $resultObj->exitCode);

        $this->assertStringContainsString('InvalidArgumentException', $resultObj->output);
    }

    public function testArrayWithNoValShouldYieldError()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithOptions(self::TEST_SECTION_2_NAME, self::TEST_SETTING_2_1_NAME);

        // The CLI error code should be >0 indicating failure.
        $this->assertGreaterThan(0, $resultObj->exitCode);

        $this->assertStringContainsString('InvalidArgumentException', $resultObj->output);
    }

    //
    //*************************************************************************
    // Tests for nonexistent data.
    //*************************************************************************
    //
    public function testUsingOptsNonExistentSectionShouldYieldEmpty()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithOptions(self::CLASS_NAME_SHORT . '_Section_does_not_exist', self::TEST_SETTING_1_1_NAME);

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $expectedValue = self::MSG_NOTHING_FOUND;
        $this->assertEquals($expectedValue, $resultObj->output);
    }

    public function testUsingArgsNonExistentSectionShouldYieldEmpty()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithArguments(self::CLASS_NAME_SHORT . '_Section_does_not_exist', self::TEST_SETTING_1_1_NAME);

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $expectedValue = self::MSG_NOTHING_FOUND;
        $this->assertEquals($expectedValue, $resultObj->output);
    }

    public function testUsingOptsNonExistentSectionAndSettingShouldYieldEmpty()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithOptions(self::CLASS_NAME_SHORT . '_Section_does_not_exist', self::CLASS_NAME_SHORT . '_Setting_does_not_exist');

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $expectedValue = self::MSG_NOTHING_FOUND;
        $this->assertEquals($expectedValue, $resultObj->output);
    }

    public function testUsingArgsNonExistentSectionAndSettingShouldYieldEmpty()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithArguments(self::CLASS_NAME_SHORT . '_Section_does_not_exist', self::CLASS_NAME_SHORT . '_Setting_does_not_exist');

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $expectedValue = self::MSG_NOTHING_FOUND;
        $this->assertEquals($expectedValue, $resultObj->output);
    }

    public function testUsingOptsNonExistentSettingShouldYieldEmpty()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithOptions(self::TEST_SECTION_1_NAME, self::CLASS_NAME_SHORT . '_Setting_does_not_exist');

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $expectedValue = self::MSG_NOTHING_FOUND;
        $this->assertEquals($expectedValue, $resultObj->output);
    }

    public function testUsingArgsNonExistentSettingShouldYieldEmpty()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithArguments(self::TEST_SECTION_1_NAME, self::CLASS_NAME_SHORT . '_Setting_does_not_exist');

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $expectedValue = self::MSG_NOTHING_FOUND;
        $this->assertEquals($expectedValue, $resultObj->output);
    }

    public function testUsingOptsArrayWithInvalidValShouldYieldEmpty()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithOptions(self::TEST_SECTION_2_NAME, self::TEST_SETTING_2_1_NAME, self::CLASS_NAME_SHORT . '_Array_key_does_not_exist');

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $expectedValue = self::MSG_NOTHING_FOUND;
        $this->assertEquals($expectedValue, $resultObj->output);
    }

    public function testUsingArgsArrayWithInvalidValShouldYieldEmpty()
    {

        // Pass empty section name.
        $resultObj = $this->runCommandWithArguments(self::TEST_SECTION_2_NAME, self::TEST_SETTING_2_1_NAME, self::CLASS_NAME_SHORT . '_Array_key_does_not_exist');

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $expectedValue = self::MSG_NOTHING_FOUND;
        $this->assertEquals($expectedValue, $resultObj->output);
    }

    //
    //*************************************************************************
    // Tests for existing data.
    //*************************************************************************
    //

    public function testUsingOptsDeleteSingleSetting()
    {

        $resultObj = $this->runCommandWithOptions(self::TEST_SECTION_1_NAME, self::TEST_SETTING_1_1_NAME);

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $this->assertStringContainsString('Success:', $resultObj->output);

        $config = $this->makeNewConfig();
        $configDump = $config->dumpConfig();
        $needle = self::TEST_SETTING_1_1_NAME . ' = ' . self::TEST_SETTING_1_1_VALUE;
        $this->assertStringNotContainsString($needle, $configDump);
    }

    public function testUsingArgsDeleteSingleSetting()
    {

        $resultObj = $this->runCommandWithArguments(self::TEST_SECTION_1_NAME, self::TEST_SETTING_1_2_NAME);

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $this->assertStringContainsString('Success:', $resultObj->output);

        $config = $this->makeNewConfig();
        $configDump = $config->dumpConfig();
        $needle = self::TEST_SETTING_1_1_NAME . ' = ' . self::TEST_SETTING_1_1_VALUE;
        $this->assertStringNotContainsString($needle, $configDump);
    }

    public function testUsingOptsDeleteArraySetting()
    {

        $resultObj = $this->runCommandWithOptions(self::TEST_SECTION_2_NAME, self::TEST_SETTING_2_1_NAME, self::TEST_SETTING_2_1_VALUE_0);

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $this->assertStringContainsString('Success:', $resultObj->output);

        $config = $this->makeNewConfig();
        $configDump = $config->dumpConfig();
        $needle = self::TEST_SETTING_1_1_NAME . ' = ' . self::TEST_SETTING_1_1_VALUE;
        $this->assertStringNotContainsString($needle, $configDump);
    }

    public function testUsingArgsDeleteArraySetting()
    {

        $resultObj = $this->runCommandWithArguments(self::TEST_SECTION_2_NAME, self::TEST_SETTING_2_1_NAME, self::TEST_SETTING_2_1_VALUE_2);

        // The CLI error code should be 0 indicating success.
        $this->assertEquals(0, $resultObj->exitCode, $this->getCommandDisplayOutputErrorMessage());

        $this->assertStringContainsString('Success:', $resultObj->output);

        $config = $this->makeNewConfig();
        $configDump = $config->dumpConfig();
        $needle = self::TEST_SETTING_1_1_NAME . ' = ' . self::TEST_SETTING_1_1_VALUE;
        $this->assertStringNotContainsString($needle, $configDump);
    }
}
