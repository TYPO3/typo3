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

namespace TYPO3\CMS\Install\Tests\Functional\Report;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Service\UpgradeWizardsService;
use TYPO3\CMS\Install\Report\InstallStatusReport;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class InstallStatusReportTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['reports'];

    #[Test]
    public function getStatusDoesNotCreateTemporaryFolderInFileadmin(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        new InstallStatusReport($this->get(UpgradeWizardsService::class), $this->get(UriBuilder::class))->getStatus();

        self::assertDirectoryDoesNotExist(Environment::getPublicPath() . '/fileadmin/_temp_');
    }
}
