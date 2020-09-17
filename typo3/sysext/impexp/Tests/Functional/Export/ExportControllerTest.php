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

namespace TYPO3\CMS\Impexp\Tests\Functional\Export;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Impexp\Controller\ExportController;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

/**
 * Test case
 */
class ExportControllerTest extends AbstractImportExportTestCase
{
    /**
     * @var ExportController|MockObject|AccessibleObjectInterface
     */
    protected $exportControllerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exportControllerMock = $this->getAccessibleMock(ExportController::class, ['dummy'], [], '', false);
        $this->exportControllerMock->_set('lang', $this->createMock(LanguageService::class));
    }

    /**
     * @test
     */
    public function tableSelectOptionsContainPresetsTable()
    {
        $tables = $this->exportControllerMock->_call('getTableSelectOptions');
        self::assertArrayHasKey('tx_impexp_presets', $tables);
    }
}
