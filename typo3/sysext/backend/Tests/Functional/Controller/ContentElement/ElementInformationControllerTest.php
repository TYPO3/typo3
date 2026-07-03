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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\ContentElement;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ElementInformationControllerTest extends FunctionalTestCase
{
    private const string FIXTURE_PATH = 'typo3/sysext/backend/Tests/Functional/Controller/ContentElement/Fixtures/ElementInformation';

    protected array $coreExtensionsToLoad = ['filemetadata'];

    protected array $pathsToProvideInTestInstance = [
        self::FIXTURE_PATH . '/Files/image.svg' => 'fileadmin/user_upload/image.svg',
        self::FIXTURE_PATH . '/Files/video.mp4' => 'fileadmin/user_upload/video.mp4',
        self::FIXTURE_PATH . '/file-storages.yaml' => 'typo3conf/system/file-storages.yaml',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementInformation/ElementInformationFiles.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function imageDimensionsAreShownOnce(): void
    {
        $body = $this->renderInformationOfFile('1:/user_upload/image.svg');

        self::assertSame(1, substr_count($body, '<th class="col-fieldname">Width</th>'));
        self::assertSame(1, substr_count($body, '<th class="col-fieldname">Height</th>'));
        self::assertStringContainsString('<td class="col-word-break">1200px</td>', $body);
        self::assertStringContainsString('<td class="col-word-break">800px</td>', $body);
    }

    #[Test]
    public function metadataFieldsOfTheFileTypeAreShown(): void
    {
        $body = $this->renderInformationOfFile('1:/user_upload/video.mp4');

        self::assertStringContainsString('<th class="col-fieldname">Duration (in seconds)</th>', $body);
        self::assertStringNotContainsString('<th class="col-fieldname">Width</th>', $body);
        self::assertStringNotContainsString('<th class="col-fieldname">Height</th>', $body);
        self::assertStringNotContainsString('<th class="col-fieldname">Latitude</th>', $body);
        self::assertStringNotContainsString('<th class="col-fieldname">Number of Pages</th>', $body);
    }

    private function renderInformationOfFile(string $combinedIdentifier): string
    {
        $request = new ServerRequest('https://example.com/typo3/record/info', 'GET')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $this->get(Router::class)->getRoute('show_item'))
            ->withQueryParams(['table' => '_FILE', 'uid' => $combinedIdentifier]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return (string)$this->get(ElementInformationController::class)->mainAction($request)->getBody();
    }
}
