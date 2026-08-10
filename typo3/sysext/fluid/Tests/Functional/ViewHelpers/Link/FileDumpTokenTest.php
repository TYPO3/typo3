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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Link;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Controller\FileDumpController;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * The file dump URLs created by the file link ViewHelper must be accepted
 * by the controller which validates them.
 */
final class FileDumpTokenTest extends FunctionalTestCase
{
    private const TEMPLATE_PATH = 'EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/Link/FileViewHelper/Template.fluid.html';

    protected array $additionalFoldersToCreate = [
        '/fileadmin/user_upload',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/Link/FileViewHelper/Folders/fileadmin/user_upload/typo3_image2.jpg' => 'fileadmin/user_upload/typo3_image2.jpg',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ViewHelpers/Link/FileViewHelper/DatabaseImport.csv');
        $this->get(ConnectionPool::class)
            ->getConnectionForTable('sys_file_storage')
            ->update('sys_file_storage', ['is_public' => 0], ['uid' => 1]);
    }

    public static function renderedLinkDataProvider(): \Generator
    {
        yield 'plain link' => [0];
        yield 'link with alternative file name' => [2];
        yield 'download link' => [3];
        yield 'download link with alternative file name' => [6];
    }

    #[DataProvider('renderedLinkDataProvider')]
    #[Test]
    public function fileDumpUrlOfLinkViewHelperIsAcceptedByFileDumpController(int $tagIndex): void
    {
        $queryParameters = $this->getQueryParametersOfRenderedLink($tagIndex);
        self::assertSame('dumpFile', $queryParameters['eID'] ?? null);

        $request = (new ServerRequest('https://localhost/index.php', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withQueryParams($queryParameters);

        self::assertSame(200, $this->get(FileDumpController::class)->dumpAction($request)->getStatusCode());
    }

    private function getQueryParametersOfRenderedLink(int $tagIndex): array
    {
        $normalizedParams = NormalizedParams::createFromServerParams(['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/index.php']);
        $request = (new ServerRequest('https://localhost/', 'GET'))
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplatePathAndFilename(self::TEMPLATE_PATH);
        $view = new TemplateView($context);
        $view->assign('file', $this->get(ResourceFactory::class)->retrieveFileOrFolderObject(1));

        $tags = array_values(array_filter(explode(LF, $view->render())));
        self::assertArrayHasKey($tagIndex, $tags);
        self::assertMatchesRegularExpression('/href="([^"]+)"/', $tags[$tagIndex]);
        preg_match('/href="([^"]+)"/', $tags[$tagIndex], $matches);

        parse_str((string)parse_url(htmlspecialchars_decode($matches[1]), PHP_URL_QUERY), $queryParameters);
        return $queryParameters;
    }
}
