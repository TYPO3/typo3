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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Install\Updates\MigrateSiteSettingsConfigUpdate;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MigrateSiteSettingsConfigUpdateTest extends FunctionalTestCase
{
    #[Test]
    public function upgradeSettingsUpdateWithSettings(): void
    {
        $siteconfigurationIdentifier = 'settings';

        $this->get(SiteWriter::class)->write(
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
                        'locale' => 'en_US.UTF-8',
                        'navigationTitle' => 'English',
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

    #[Test]
    public function upgradeSettingsUpdateWithoutSettings(): void
    {
        $siteconfigurationIdentifier = 'withoutSettings';

        $this->get(SiteWriter::class)->write(
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
                        'locale' => 'en_US.UTF-8',
                        'navigationTitle' => 'English',
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
