<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\Integration;

use Piwik\Option;
use Piwik\Http;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Tests\Framework\Fixture;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests to call the archive.php script via web and check there is no error.
 *
 * @group Core
 * @group ArchiveWebTest
 */
class ArchiveWebTest extends SystemTestCase
{
    public function test_WebArchiving()
    {
        $host  = Fixture::getRootUrl();
        $token = Fixture::getTokenAuth();

        $urlTmp = Option::get('piwikUrl');
        Option::set('piwikUrl', $host . 'tests/PHPUnit/proxy/index.php');

        $url    = $host . 'tests/PHPUnit/proxy/archive.php?token_auth=' . $token;
        $output = Http::sendHttpRequest($url, 6);

        $this->assertEquals("- 1 ['0' => 'mock output'] [] [idsubtable = ]<br />", $output);

        if (!empty($urlTmp)) {
            Option::set('piwikUrl', $urlTmp);
        } else {
            Option::delete('piwikUrl');
        }

    }

    public function test_WebArchiveScriptCanBeRun_WithPhpCgi_AndWithoutTokenAuth()
    {
        list($returnCode, $output) = $this->runArchivePhpScriptWithPhpCgi();

        $this->assertEquals(0, $returnCode, "Output: " . $output);
        $this->assertStringStartsWith('mock output', $output);
    }

    private function runArchivePhpScriptWithPhpCgi()
    {
        $command = "php-cgi \"" . PIWIK_INCLUDE_PATH . "/tests/PHPUnit/proxy/archive.php" . "\"";

        exec($command, $output, $returnCode);

        $output = implode("\n", $output);

        return array($returnCode, $output);
    }

    public static function provideContainerConfigBeforeClass()
    {
        return array(
            'Psr\Log\LoggerInterface' => \DI\get('Monolog\Logger'),
            'Tests.log.allowAllHandlers' => true,
            'observers.global' => [
                ['API.Request.intercept', \DI\value(function (&$returnedValue, $finalParameters, $pluginName, $methodName, $parametersRequest) {
                    if ($pluginName == 'CoreAdminHome' && $methodName == 'runCronArchiving') {
                        $returnedValue = 'mock output';
                    }
                })],
                ['Console.doRun', \DI\value(function (&$exitCode, InputInterface $input, OutputInterface $output) {
                    if ($input->getFirstArgument() == 'core:archive') {
                        $output->writeln('mock output');
                        $exitCode = 0;
                    }
                })],
            ],
        );
    }
}
