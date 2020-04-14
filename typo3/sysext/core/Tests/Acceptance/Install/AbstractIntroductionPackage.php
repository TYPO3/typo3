<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Tests\Acceptance\Install;

use TYPO3\CMS\Core\Tests\Acceptance\Support\InstallTester;

class AbstractIntroductionPackage
{

    /**
     * @param InstallTester $I
     */
    protected function manipulateSiteConfigurationOnlyForTesting(InstallTester $I): void
    {
        $trustedHostsPatternConfig = '<?php' . PHP_EOL
            . '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'trustedHostsPattern\'] = \'web:8000\';';
        $I->writeToFile('typo3conf/AdditionalConfiguration.php', $trustedHostsPatternConfig);

        $configFile = __DIR__ . '/../../../../../../typo3temp/var/tests/acceptance/typo3conf/sites/introduction/config.yaml';
        $config = file($configFile);
        if (strpos($config[0], 'base: /') !== false) {
            $I->amGoingTo('manipulate base in sites config');
            $config[0] = 'base: http://web:8000/typo3temp/var/tests/acceptance/' . PHP_EOL;
            file_put_contents($configFile, $config);
        }
        $I->amOnPage('/typo3');
        $I->click('Maintenance');
        $I->switchToContentFrame();
        $I->click('Flush cache');
    }
}
