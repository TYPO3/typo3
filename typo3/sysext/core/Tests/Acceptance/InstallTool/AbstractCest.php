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

namespace TYPO3\CMS\Core\Tests\Acceptance\InstallTool;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AbstractCest
{
    public const ENABLE_INSTALL_TOOL_FILEPATH = 'typo3conf/ENABLE_INSTALL_TOOL';
    public const ADDITIONAL_CONFIGURATION_FILEPATH = 'typo3conf/AdditionalConfiguration.php';

    public function _before(BackendTester $I)
    {
        $I->amOnPage('typo3/install.php');
    }

    public function _after(BackendTester $I)
    {
        $I->click('Logout');

        $I->amGoingTo('clean up created files');
        unlink(Environment::getProjectPath() . '/' . self::ADDITIONAL_CONFIGURATION_FILEPATH);

        $I->dontSeeFileFound(self::ENABLE_INSTALL_TOOL_FILEPATH);
        $I->dontSeeFileFound(self::ADDITIONAL_CONFIGURATION_FILEPATH);
    }

    protected function logIntoInstallTool(BackendTester $I)
    {
        $password = $this->setInstallToolPassword($I);

        $I->writeToFile(AbstractCest::ENABLE_INSTALL_TOOL_FILEPATH, '');
        $I->reloadPage();
        $I->fillField('#t3-install-form-password', $password);
        $I->click('Login');
        $I->waitForText('Maintenance');
    }

    /**
     * @param BackendTester $I
     * @return string
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    private function setInstallToolPassword(BackendTester $I): string
    {
        $hashMethod = GeneralUtility::makeInstance(Argon2iPasswordHash::class);
        $password = 'temporary password';
        $hashedPassword = $hashMethod->getHashedPassword($password);
        $I->writeToFile(
            self::ADDITIONAL_CONFIGURATION_FILEPATH,
            '<?php' . PHP_EOL . '$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'installToolPassword\'] = \'' . $hashedPassword . '\';'
        );
        return $password;
    }
}
