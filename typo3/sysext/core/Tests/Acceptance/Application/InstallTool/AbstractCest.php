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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\InstallTool;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AbstractCest
{
    public const ENABLE_INSTALL_TOOL_FILEPATH = 'typo3conf/ENABLE_INSTALL_TOOL';
    public const ADDITIONAL_CONFIGURATION_FILEPATH = 'typo3conf/AdditionalConfiguration.php';
    public const INSTALL_TOOL_PASSWORD = 'temporary password';

    public function _before(ApplicationTester $I): void
    {
        $I->amOnPage('typo3/install.php');
    }

    public function _after(ApplicationTester $I): void
    {
        $I->click('Logout');
        // Make sure logout has finished
        $I->waitForText('The Install Tool is locked', 20);

        $I->amGoingTo('clean up created files');
        unlink(Environment::getProjectPath() . '/' . self::ADDITIONAL_CONFIGURATION_FILEPATH);

        $I->dontSeeFileFound(Environment::getProjectPath() . '/' . self::ENABLE_INSTALL_TOOL_FILEPATH);
        $I->dontSeeFileFound(Environment::getProjectPath() . '/' . self::ADDITIONAL_CONFIGURATION_FILEPATH);
    }

    protected function logIntoInstallTool(ApplicationTester $I): void
    {
        $password = $this->setInstallToolPassword($I);

        $I->writeToFile(AbstractCest::ENABLE_INSTALL_TOOL_FILEPATH, '');
        $I->reloadPage();
        $I->waitForElementVisible('#t3-install-form-password');
        $I->fillField('#t3-install-form-password', $password);
        $I->click('Login');
        $I->waitForText('Maintenance');
    }

    /**
     * @throws InvalidPasswordHashException
     */
    private function setInstallToolPassword(ApplicationTester $I): string
    {
        $hashMethod = GeneralUtility::makeInstance(Argon2iPasswordHash::class);
        $password = self::INSTALL_TOOL_PASSWORD;
        $hashedPassword = $hashMethod->getHashedPassword($password);
        $I->writeToFile(
            self::ADDITIONAL_CONFIGURATION_FILEPATH,
            '<?php' . PHP_EOL . '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'installToolPassword\'] = \'' . $hashedPassword . '\';'
        );
        return $password;
    }
}
