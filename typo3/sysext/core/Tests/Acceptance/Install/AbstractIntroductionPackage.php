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
        $acceptanceUrl = $I->grabModuleConfig('WebDriver', 'url');
        $acceptanceUrlWithTrailingSlash = rtrim($acceptanceUrl, '/') . '/';
        $acceptanceHost = $this->getHostWithPortFromUrl($acceptanceUrl);

        $trustedHostsPatternConfig = '<?php' . PHP_EOL
            . '$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'trustedHostsPattern\'] = \'' . $acceptanceHost . '\';';
        $I->writeToFile('typo3conf/AdditionalConfiguration.php', $trustedHostsPatternConfig);

        $configFile = __DIR__ . '/../../../../../../typo3temp/var/tests/acceptance/typo3conf/sites/introduction/config.yaml';
        $config = file($configFile);
        if (strpos($config[0], 'base: /') !== false) {
            $I->amGoingTo('manipulate base in sites config');
            $config[0] = 'base: ' . $acceptanceUrlWithTrailingSlash . PHP_EOL;
            file_put_contents($configFile, $config);
        }
        $I->amOnPage('/typo3');
        $I->click('Maintenance');
        $I->switchToContentFrame();

        try {
            // fill in sudo mode password
            $I->see('Confirm with user password');
            $I->fillField('confirmationPassword', 'password');
            $I->click('Confirm');
            $I->wait(10);
            // wait for Maintenance headline being available
            $I->waitForText('Maintenance');
            $I->canSee('Maintenance', 'h1');
        } catch (\Exception $e) {
            // nothing...
        }

        $I->click('Flush cache');
    }

    /**
     * @param string $url
     * @return string Host with port
     */
    protected function getHostWithPortFromUrl(string $url): string
    {
        $urlParts = parse_url($url);
        return $urlParts['host'] . (isset($urlParts['port']) ? ':' . $urlParts['port'] : '');
    }
}
