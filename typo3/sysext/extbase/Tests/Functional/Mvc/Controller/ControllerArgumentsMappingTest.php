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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Controller\BlogController;

final class ControllerArgumentsMappingTest extends FunctionalTestCase
{
    private Request $request;
    private BlogController $controller;

    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixture/ControllerArgumentsMappingTestImport.csv');

        $configuration = [
            'persistence' => [
                'storagePid' => 20,
            ],
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $this->request = new Request($serverRequest);
        $this->request = $this->request->withPluginName('Pi1');
        $this->request = $this->request->withControllerExtensionName(BlogController::class);
        $this->request = $this->request->withControllerName('Blog');
        $this->request = $this->request->withFormat('html');
        $this->request = $this->request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $this->request;

        $this->controller = $this->get(BlogController::class);
    }

    public static function actionGetsBlogFromUidArgumentDataProvider(): array
    {
        return [
            [
                'language' => 0,
                'blogUid' => 1,
                'expectedTitle' => 'Blog 1',
            ],
            [
                'language' => 1,
                'blogUid' => 1,
                'expectedTitle' => 'Blog 1 DA',
            ],
        ];
    }

    #[DataProvider('actionGetsBlogFromUidArgumentDataProvider')]
    #[Test]
    public function actionGetsBlogFromUidArgument(int $language, int $blogUid, string $expectedTitle): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect($language, $language, LanguageAspect::OVERLAYS_ON));
        $this->request = $this->request->withControllerActionName('details');
        $this->request = $this->request->withArgument('blog', $blogUid);

        $response = $this->controller->processRequest($this->request);

        $response->getBody()->rewind();
        self::assertEquals($expectedTitle, $response->getBody()->getContents());
    }

    #[Test]
    public function actionThrowsRequiredArgumentMissingExceptionWhenNoBlogGiven(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->request = $this->request->withControllerActionName('testSingle');

        $this->expectException(RequiredArgumentMissingException::class);
        $this->expectExceptionCode(1298012500);
        $this->controller->processRequest($this->request);
    }

    #[Test]
    public function actionHandlesArgumentMissingExceptionAsPageNotFoundIfConfigured(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->request = $this->request->withControllerActionName('testSingle');

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration([
            'persistence' => [
                'storagePid' => 20,
            ],
            'mvc' => [
                'showPageNotFoundIfRequiredArgumentIsMissingException' => 1,
            ],
        ]);

        try {
            $this->controller->processRequest($this->request);
            self::fail('processRequest() did not throw a PropagateResponseException although expected');
        } catch (PropagateResponseException $exception) {
            self::assertEquals(404, $exception->getResponse()->getStatusCode());
        }
    }

    #[Test]
    public function actionThrowsTargetNotFoundExceptionExceptionWhenBlogUidNotFound(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->request = $this->request->withControllerActionName('testSingle');
        $this->request = $this->request->withArgument('blog', 11);

        $this->expectException(TargetNotFoundException::class);
        $this->expectExceptionCode(1297933823);
        $this->controller->processRequest($this->request);
    }

    #[Test]
    public function actionHandlesTargetNotFoundExceptionAsPageNotFoundIfConfigured(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->request = $this->request->withControllerActionName('testSingle');
        $this->request = $this->request->withArgument('blog', 11);

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration([
            'persistence' => [
                'storagePid' => 20,
            ],
            'mvc' => [
                'showPageNotFoundIfTargetNotFoundException' => 1,
            ],
        ]);

        try {
            $this->controller->processRequest($this->request);
            self::fail('processRequest() did not throw a PropagateResponseException although expected');
        } catch (PropagateResponseException $exception) {
            self::assertEquals(404, $exception->getResponse()->getStatusCode());
        }
    }
}
