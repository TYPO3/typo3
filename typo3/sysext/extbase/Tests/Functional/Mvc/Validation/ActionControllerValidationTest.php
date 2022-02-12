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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Validation;

use ExtbaseTeam\BlogExample\Controller\BlogController;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests for the validation logic of the Extbase ActionController.
 */
class ActionControllerValidationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    /**
     * @return array
     */
    public function forwardedActionValidatesPreviouslyIgnoredArgumentDataProvider(): array
    {
        return [
            'new blog post' => [
                ['title' => '12'],
                ['blogPost[title]'],
                [1428504122],
            ],
            'existing blog post' => [
                ['__identity' => 1, 'title' => '12'],
                ['blogPost[__identity]', 'blogPost[title]'],
                [1428504122],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider forwardedActionValidatesPreviouslyIgnoredArgumentDataProvider
     * @param array $blogPostArgument
     * @param array $trustedProperties
     * @param array $expectedErrorCodes
     */
    public function forwardedActionValidatesPreviouslyIgnoredArgument(array $blogPostArgument, array $trustedProperties, array $expectedErrorCodes): void
    {
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('default');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'testkey';

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importCSVDataSet(__DIR__ . '/../../Persistence/Fixtures/blogs.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Persistence/Fixtures/posts.csv');

        $response = new Response();
        $request = new Request();

        $request->setControllerActionName('testForward');
        $request->setArgument('blogPost', $blogPostArgument);
        $request->setArgument('__trustedProperties', $this->generateTrustedPropertiesToken($trustedProperties));

        $referrerRequest = [];
        $referrerRequest['@action'] = 'testForm';
        $request->setArgument(
            '__referrer',
            ['@request' => $this->getHashService()->appendHmac(json_encode($referrerRequest))]
        );

        $titleMappingResults = new Result();
        $isDispatched = false;
        while (!$isDispatched) {
            $blogController = $this->getContainer()->get(BlogController::class);
            $response = $blogController->processRequest($request);
            if ($response instanceof ForwardResponse) {
                $titleMappingResults = $response->getArgumentsValidationResult()->forProperty('blogPost.title');
                $request = Dispatcher::buildRequestFromCurrentRequestAndForwardResponse($request, $response);
            } else {
                $isDispatched = true;
            }
        }

        $titleErrors = $titleMappingResults->getFlattenedErrors();
        self::assertCount(count($expectedErrorCodes), $titleErrors['']);

        $titleErrors = $titleErrors[''];
        /** @var Error $titleError */
        foreach ($titleErrors as $titleError) {
            self::assertContains($titleError->getCode(), $expectedErrorCodes);
        }
        $response->getBody()->rewind();
        self::assertEquals('testFormAction', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function validationResultsAreProvidedForTheSameObjectInDifferentArguments(): void
    {
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('default');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'testkey';

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importCSVDataSet(__DIR__ . '/../../Persistence/Fixtures/blogs.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Persistence/Fixtures/posts.csv');

        $response = new Response();
        $request = new Request();

        $request->setControllerActionName('testRelatedObject');
        $request->setArgument('blog', ['__identity' => 1, 'description' => str_repeat('test', 40)]);
        $request->setArgument(
            'blogPost',
            ['__identity' => 1, 'title' => '77', 'blog' => ['__identity' => 1, 'title' => str_repeat('test', 21)]]
        );
        $request->setArgument(
            '__trustedProperties',
            $this->generateTrustedPropertiesToken(
                [
                    'blog[__identity]',
                    'blog[description]',
                    'blogPost[__identity]',
                    'blogPost[title]',
                    'blogPost[blog][__identity]',
                    'blogPost[blog][title]',
                ]
            )
        );

        $referrerRequest = [];
        $referrerRequest['@action'] = 'testForm';
        $request->setArgument(
            '__referrer',
            ['@request' => $this->getHashService()->appendHmac(json_encode($referrerRequest))]
        );

        $isDispatched = false;
        while (!$isDispatched) {
            $blogController = $this->getContainer()->get(BlogController::class);
            $response = $blogController->processRequest($request);
            if ($response instanceof ForwardResponse) {

                /** @var Result $validationResult */
                $validationResult = $response->getArgumentsValidationResult();

                self::assertInstanceOf(ForwardResponse::class, $response);
                self::assertCount(1, $validationResult->forProperty('blog.title')->getErrors());
                self::assertCount(1, $validationResult->forProperty('blog.description')->getErrors());
                self::assertCount(1, $validationResult->forProperty('blogPost.title')->getErrors());

                $request = Dispatcher::buildRequestFromCurrentRequestAndForwardResponse($request, $response);
            } else {
                $isDispatched = true;
            }
        }

        $response->getBody()->rewind();
        self::assertEquals('testFormAction', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function argumentsOfOriginalRequestRemainOnValidationErrors(): void
    {
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('default');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'testkey';

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importCSVDataSet(__DIR__ . '/../../Persistence/Fixtures/blogs.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Persistence/Fixtures/posts.csv');

        $response = new Response();
        $request = new Request();

        $request->setControllerActionName('testRelatedObject');
        $request->setArgument('blog', ['__identity' => 1, 'description' => str_repeat('test', 40)]);
        $request->setArgument(
            'blogPost',
            ['__identity' => 1, 'title' => '77', 'blog' => ['__identity' => 1, 'title' => str_repeat('test', 21)]]
        );
        $request->setArgument(
            '__trustedProperties',
            $this->generateTrustedPropertiesToken(
                [
                    'blog[__identity]',
                    'blog[description]',
                    'blogPost[__identity]',
                    'blogPost[title]',
                    'blogPost[blog][__identity]',
                    'blogPost[blog][title]',
                ]
            )
        );

        $referrerRequest = [];
        $referrerRequest['@action'] = 'testForm';
        $request->setArgument(
            '__referrer',
            ['@request' => $this->getHashService()->appendHmac(json_encode($referrerRequest))]
        );

        $originalArguments = $request->getArguments();
        $isDispatched = false;
        while (!$isDispatched) {
            $blogController = $this->getContainer()->get(BlogController::class);
            $response = $blogController->processRequest($request);
            if ($response instanceof ForwardResponse) {
                $request = Dispatcher::buildRequestFromCurrentRequestAndForwardResponse($request, $response);
                self::assertEquals($originalArguments, $request->getOriginalRequest()->getAttribute('extbase')->getArguments());
            } else {
                $isDispatched = true;
            }
        }

        $response->getBody()->rewind();
        self::assertEquals('testFormAction', $response->getBody()->getContents());
    }

    /**
     * @param array $formFieldNames
     * @return string
     */
    protected function generateTrustedPropertiesToken(array $formFieldNames): string
    {
        $mvcPropertyMappingConfigurationService = $this->getContainer()->get(
            MvcPropertyMappingConfigurationService::class
        );
        return $mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($formFieldNames, '');
    }

    /**
     * @return HashService
     */
    protected function getHashService(): HashService
    {
        return $this->getContainer()->get(HashService::class);
    }
}
