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

namespace TYPO3\CMS\Install\Tests\Functional\Updates;

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\MigrateSiteSettingsConfigUpdate;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MigrateSiteSettingsConfigUpdateTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function upgradeSettingsUpdateWithSettings(): void
    {
        $siteconfigurationIdentifier = 'settings';

        GeneralUtility::makeInstance(SiteConfiguration::class)->write(
            $siteconfigurationIdentifier,
            [
                'rootPageId' => 1,
                'base' => 'www.test.org',
                'languages' => [
                    0 => [
                        'title' => 'English',
                        'enabled' => true,
                        'languageId' => 0,
                        'base' => '/',
                        'typo3Language' => 'default',
                        'locale' => 'en_US.UTF-8',
                        'iso-639-1' => 'en',
                        'navigationTitle' => 'English',
                        'hreflang' => 'en-us',
                        'direction' => 'ltr',
                        'flag' => 'us',
                    ],
                ],
                'settings' => [
                    'debug' => 1,
                    'test' => true,
                ],
                'errorHandling' => [],
                'routes' => [],
            ]
        );

        $subject = new MigrateSiteSettingsConfigUpdate();
        $subject->executeUpdate();
        self::assertFileExists($this->getSettingsFilePath($siteconfigurationIdentifier));
    }

    /**
     * @test
     */
    public function upgradeSettingsUpdateWithoutSettings(): void
    {
        $siteconfigurationIdentifier = 'withoutSettings';

        GeneralUtility::makeInstance(SiteConfiguration::class)->write(
            $siteconfigurationIdentifier,
            [
                'rootPageId' => 2,
                'base' => 'www.testTwo.org',
                'languages' => [
                    0 => [
                        'title' => 'English',
                        'enabled' => true,
                        'languageId' => 0,
                        'base' => '/',
                        'typo3Language' => 'default',
                        'locale' => 'en_US.UTF-8',
                        'iso-639-1' => 'en',
                        'navigationTitle' => 'English',
                        'hreflang' => 'en-us',
                        'direction' => 'ltr',
                        'flag' => 'us',
                    ],
                ],
                'errorHandling' => [],
                'routes' => [],
            ]
        );

        $subject = new MigrateSiteSettingsConfigUpdate();
        $subject->executeUpdate();
        self::assertFileDoesNotExist($this->getSettingsFilePath($siteconfigurationIdentifier));
    }

    protected function getSettingsFilePath(string $identifier): string
    {
        return Environment::getConfigPath() . '/sites/' . $identifier . '/settings.yaml';
    }
}
