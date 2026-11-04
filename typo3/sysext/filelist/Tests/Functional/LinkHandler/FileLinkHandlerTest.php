<?php

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

namespace TYPO3\CMS\Filelist\Tests\Functional\LinkHandler;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\LinkBrowserController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileLinkHandlerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['filelist'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function onlineMediaFormIsRenderedWithoutFileExtensionRestriction(): void
    {
        $content = $this->renderFileTab([]);

        self::assertStringContainsString('name="newMediaForm"', $content);
    }

    #[Test]
    public function onlineMediaFormIsNotRenderedWhenAllowedFileExtensionsExcludeOnlineMedia(): void
    {
        $content = $this->renderFileTab(['allowedFileExtensions' => 'bib']);

        self::assertStringNotContainsString('name="newMediaForm"', $content);
    }

    private function renderFileTab(array $fieldParams): string
    {
        $request = (new ServerRequest('https://www.example.com/typo3/wizard/link/browse', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''))
            ->withAttribute('route', new Route('/wizard/link/browse', ['_identifier' => 'wizard_link']))
            ->withQueryParams([
                'act' => 'file',
                'expandFolder' => '1:/',
                'P' => [
                    'table' => 'tt_content',
                    'uid' => 1,
                    'pid' => 0,
                    'field' => 'header_link',
                    'formName' => 'editform',
                    'itemName' => 'data[tt_content][1][header_link]',
                    'currentValue' => '',
                    'params' => $fieldParams,
                ],
            ]);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return (string)$this->get(LinkBrowserController::class)->mainAction($request)->getBody();
    }
}
