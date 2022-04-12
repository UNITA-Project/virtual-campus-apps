<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Diagnostics\tests\Mock;

use Piwik\Plugins\Diagnostics\Diagnostic\Diagnostic;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;

class DiagnosticWithSuccess implements Diagnostic
{
    public function execute()
    {
        return array(
            DiagnosticResult::singleResult('Success', DiagnosticResult::STATUS_OK, 'Comment'),
        );
    }
}
