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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Impexp\Controller\ExportController;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

class PresetsTest extends AbstractImportExportTestCase
{
    /**
     * @test
     */
    public function circleOfLife(): void
    {
        $presetActions = [
            ['presetAction' => ['select' => '0', 'load' => '1'], 'expected' => 'ERROR: No preset selected for loading.'],
            ['presetAction' => ['select' => '0', 'merge' => '1'], 'expected' => 'ERROR: No preset selected for loading.'],
            ['presetAction' => ['select' => '0', 'delete' => '1'], 'expected' => 'ERROR: No preset selected for deletion.'],
            ['presetAction' => ['select' => '0', 'save' => '1'], 'expected' => 'New preset "Test Preset" is created'],
            ['presetAction' => ['select' => '1', 'load' => '1'], 'expected' => 'Preset #1 loaded!'],
            ['presetAction' => ['select' => '1', 'merge' => '1'], 'expected' => 'Preset #1 merged!'],
            ['presetAction' => ['select' => '1', 'save' => '1'], 'expected' => 'Preset #1 saved!'],
            ['presetAction' => ['select' => '1', 'delete' => '1'], 'expected' => 'Preset #1 deleted!'],
            ['presetAction' => ['select' => '1', 'load' => '1'], 'expected' => 'ERROR: No valid preset #1 found.'],
            ['presetAction' => ['select' => '1', 'merge' => '1'], 'expected' => 'ERROR: No valid preset #1 found.'],
            ['presetAction' => ['select' => '1', 'save' => '1'], 'expected' => 'ERROR: No valid preset #1 found.'],
            ['presetAction' => ['select' => '1', 'delete' => '1'], 'expected' => 'ERROR: No valid preset #1 found.'],
        ];

        foreach ($presetActions as $action) {
            $this->presetAction($action['presetAction'], $action['expected']);
        }
    }

    /**
     * @param array $presetAction
     * @param string $expected
     */
    protected function presetAction(array $presetAction, string $expected): void
    {
        $inData = [
            'pagetree' =>
                [
                    'id' => '79',
                    'levels' => '0',
                    'tables' => ['sys_file'],
                ],
            'external_ref' =>
                [
                    'tables' => ['sys_file_metadata'],
                ],
            'external_static' =>
                [
                    'tables' => ['sys_file_collection'],
                ],
            'showStaticRelations' => '',
            'excludeDisabled' => '1',
            'preset' =>
                [
                    'title' => 'Test Preset',
                    'public' => '',
                ],
            'meta' =>
                [
                    'title' => '',
                    'description' => '',
                    'notes' => '',
                ],
            'filetype' => 'xml',
            'filename' => '',
            'excludeHTMLfileResources' => '',
            'saveFilesOutsideExportFile' => '',
            'extension_dep' => '',
            'softrefCfg' => [],
        ];

        $uriBuilderMock = $this->getAccessibleMock(UriBuilder::class, ['dummy'], [], '', false);
        $iconFactoryMock = $this->getAccessibleMock(IconFactory::class, ['dummy'], [], '', false);
        $pageRendererMock = $this->getAccessibleMock(PageRenderer::class, ['dummy'], [], '', false);
        $moduleTemplateMock = $this->getAccessibleMock(ModuleTemplate::class, ['addFlashMessage'], [], '', false);
        $moduleTemplateMock->expects(self::once())->method('addFlashMessage')->with(self::equalTo($expected));
        $moduleTemplateFactoryMock = $this->getAccessibleMock(ModuleTemplateFactory::class, ['create'], [], '', false);
        $moduleTemplateFactoryMock->method('create')->willReturn($moduleTemplateMock);
        $responseFactory = $this->getAccessibleMock(ResponseFactory::class, ['dummy'], [], '', false);

        $subject = $this->getAccessibleMock(ExportController::class, ['addFlashMessage'], [
            $iconFactoryMock,
            $pageRendererMock,
            $uriBuilderMock,
            $moduleTemplateFactoryMock,
            $responseFactory,
        ]);
        $subject->_set('moduleTemplate', $moduleTemplateMock);
        $inData = $subject->preprocessInputData($inData);
        $subject->processPresets($presetAction, $inData);
    }
}
