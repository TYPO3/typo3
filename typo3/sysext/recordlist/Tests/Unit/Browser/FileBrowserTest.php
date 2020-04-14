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

namespace TYPO3\CMS\Recordlist\Tests\Unit\Browser;

use Prophecy\Argument;
use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recordlist\Browser\FileBrowser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FileBrowserTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderGetsUserDefaultUploadFolderForCurrentPageData(): void
    {
        [$moduleTemplate, $beUser] = $this->setupProphecies();

        $bparams = '|||gif,png,svg|data-4-pages-4-nav_icon-sys_file_reference';
        $fileBrowser = $this->getAccessibleMock(FileBrowser::class, ['dummy'], [], '', false);
        $fileBrowser->_set('bparams', $bparams);
        $fileBrowser->_set('moduleTemplate', $moduleTemplate);
        $fileBrowser->render();

        $beUser->getTSConfig()->shouldHaveBeenCalled();
        $beUser->getDefaultUploadFolder(4, 'pages', 'nav_icon')->shouldHaveBeenCalled();
    }

    /**
     * @return array
     */
    private function setupProphecies(): array
    {
        $browserFolderTreeView = $this->prophesize(ElementBrowserFolderTreeView::class);
        GeneralUtility::addInstance(ElementBrowserFolderTreeView::class, $browserFolderTreeView->reveal());

        $flashMessageService = $this->prophesize(FlashMessageService::class);
        $flashMessageService->getMessageQueueByIdentifier()->willReturn($this->prophesize(FlashMessageQueue::class)->reveal());
        $moduleTemplate = $this->getAccessibleMock(ModuleTemplate::class, ['setupPage'], [], '', false);
        $moduleTemplate->_set('flashMessageService', $flashMessageService->reveal());
        $moduleTemplate->_set('view', $this->prophesize(StandaloneView::class)->reveal());
        $moduleTemplate->_set('docHeaderComponent', $this->prophesize(DocHeaderComponent::class)->reveal());
        $moduleTemplate->_set('pageRenderer', $this->prophesize(PageRenderer::class)->reveal());

        $lang = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $lang->reveal();

        $beUser = $this->prophesize(BackendUserAuthentication::class);
        $beUser->getFileStorages()->willReturn([]);
        $beUser->getTSConfig()->willReturn('');
        $beUser->getModuleData(Argument::cetera())->willReturn([]);
        $beUser->getDefaultUploadFolder(Argument::cetera())->willReturn('');
        $GLOBALS['BE_USER'] = $beUser->reveal();
        return [$moduleTemplate, $beUser];
    }
}
