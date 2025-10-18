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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Security\HashScope;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Controller\BlogController;

/**
 * Tests for the validation logic of the Extbase ActionController.
 */
final class ActionControllerValidationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ActionControllerValidationTestImport.csv');
    }

    public static function forwardedActionValidatesPreviouslyIgnoredArgumentDataProvider(): array
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

    #[DataProvider('forwardedActionValidatesPreviouslyIgnoredArgumentDataProvider')]
    #[Test]
    public function forwardedActionValidatesPreviouslyIgnoredArgument(array $blogPostArgument, array $trustedProperties, array $expectedErrorCodes): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'testkey';

        $response = new Response();
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;
        $request = new Request($serverRequest);

        $request = $request->withControllerActionName('testForward');
        $request = $request->withArgument('blogPost', $blogPostArgument);
        $request = $request->withArgument('__trustedProperties', $this->generateTrustedPropertiesToken($trustedProperties));

        $referrerRequest = [];
        $referrerRequest['@action'] = 'testForm';
        $request = $request->withArgument(
            '__referrer',
            ['@request' => (new HashService())->appendHmac(json_encode($referrerRequest), HashScope::ReferringRequest->prefix())]
        );

        $titleMappingResults = new Result();
        $isDispatched = false;
        while (!$isDispatched) {
            $blogController = $this->get(BlogController::class);
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

    #[Test]
    public function validationResultsAreProvidedForTheSameObjectInDifferentArguments(): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'testkey';

        $response = new Response();
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;
        $request = new Request($serverRequest);

        $request = $request->withControllerActionName('testRelatedObject');
        $request = $request->withArgument('blog', ['__identity' => 1, 'description' => str_repeat('test', 40)]);
        $request = $request->withArgument(
            'blogPost',
            ['__identity' => 1, 'title' => '77', 'blog' => ['__identity' => 1, 'title' => str_repeat('test', 21)]]
        );
        $request = $request->withArgument(
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
        $request = $request->withArgument(
            '__referrer',
            ['@request' => (new HashService())->appendHmac(json_encode($referrerRequest), HashScope::ReferringRequest->prefix())]
        );

        $isDispatched = false;
        while (!$isDispatched) {
            $blogController = $this->get(BlogController::class);
            $response = $blogController->processRequest($request);
            if ($response instanceof ForwardResponse) {
                /** @var Result $validationResult */
                $validationResult = $response->getArgumentsValidationResult();

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

    #[Test]
    public function argumentsOfOriginalRequestRemainOnValidationErrors(): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'testkey';

        $response = new Response();
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = new Request($serverRequest);

        $request = $request->withControllerActionName('testRelatedObject');
        $request = $request->withArgument('blog', ['__identity' => 1, 'description' => str_repeat('test', 40)]);
        $request = $request->withArgument(
            'blogPost',
            ['__identity' => 1, 'title' => '77', 'blog' => ['__identity' => 1, 'title' => str_repeat('test', 21)]]
        );
        $request = $request->withArgument(
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
        $request = $request->withArgument(
            '__referrer',
            ['@request' => (new HashService())->appendHmac(json_encode($referrerRequest), HashScope::ReferringRequest->prefix())]
        );
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $originalArguments = $request->getArguments();
        $isDispatched = false;
        while (!$isDispatched) {
            $blogController = $this->get(BlogController::class);
            $response = $blogController->processRequest($request);
            if ($response instanceof ForwardResponse) {
                $request = Dispatcher::buildRequestFromCurrentRequestAndForwardResponse($request, $response);
                self::assertEquals($originalArguments, $request->getAttribute('extbase')->getOriginalRequest()->getAttribute('extbase')->getArguments());
            } else {
                $isDispatched = true;
            }
        }

        $response->getBody()->rewind();
        self::assertEquals('testFormAction', $response->getBody()->getContents());
    }

    protected function generateTrustedPropertiesToken(array $formFieldNames): string
    {
        $mvcPropertyMappingConfigurationService = $this->get(
            MvcPropertyMappingConfigurationService::class
        );
        return $mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($formFieldNames, '');
    }
}
