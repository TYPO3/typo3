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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class IndexConfigHiddenFieldTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['indexed_search'];

    #[Test]
    public function editorCanEnableIndexConfigurationWithoutFieldPermission(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/IndexConfigHiddenField/Base.csv');
        $backendUser = $this->setUpBackendUser(2);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['index_config' => [1 => ['hidden' => 0]]], [], $backendUser);
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/IndexConfigHiddenField/Enabled.csv');
    }
}
