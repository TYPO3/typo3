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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Validation;

use ExtbaseTeam\BlogExample\Controller\BlogController;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests for the validation logic of the Extbase ActionController.
 */
class ActionControllerValidationTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'
    ];

    /**
     * @return array
     */
    public function forwardedActionValidatesPreviouslyIgnoredArgumentDataProvider()
    {
        return [
            'new blog post' => [
                ['title' => '12'],
                ['blogPost[title]'],
                [1428504122]
            ],
            'existing blog post' => [
                ['__identity' => 1, 'title' => '12'],
                ['blogPost[__identity]', 'blogPost[title]'],
                [1428504122]
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
    public function forwardedActionValidatesPreviouslyIgnoredArgument(array $blogPostArgument, array $trustedProperties, array $expectedErrorCodes)
    {
        $GLOBALS['LANG'] = GeneralUtility::getContainer()->get(LanguageService::class);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'testkey';

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');

        $objectManager = $this->getObjectManager();
        $response = $objectManager->get(Response::class);
        $request = $objectManager->get(Request::class);

        $request->setControllerActionName('testForward');
        $request->setArgument('blogPost', $blogPostArgument);
        $request->setArgument('__trustedProperties', $this->generateTrustedPropertiesToken($trustedProperties));

        $referrerRequest = [];
        $referrerRequest['@action'] = 'testForm';
        $request->setArgument(
            '__referrer',
            ['@request' => $this->getHashService()->appendHmac(json_encode($referrerRequest))]
        );

        while (!$request->isDispatched()) {
            try {
                $blogController = $objectManager->get(BlogController::class);
                $blogController->processRequest($request, $response);
            } catch (StopActionException $e) {
            }
        }

        /* @var \TYPO3\CMS\Extbase\Error\Error $titleLengthError */
        $titleMappingResults = $request->getOriginalRequestMappingResults()->forProperty('blogPost.title');
        $titleErrors = $titleMappingResults->getFlattenedErrors();
        self::assertCount(count($expectedErrorCodes), $titleErrors['']);

        $titleErrors = $titleErrors[''];
        /** @var Error $titleError */
        foreach ($titleErrors as $titleError) {
            self::assertContains($titleError->getCode(), $expectedErrorCodes);
        }
        self::assertEquals('testFormAction', $response->getContent());
    }

    /**
     * @test
     */
    public function validationResultsAreProvidedForTheSameObjectInDifferentArguments()
    {
        $GLOBALS['LANG'] = GeneralUtility::getContainer()->get(LanguageService::class);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'testkey';

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.xml');

        $objectManager = $this->getObjectManager();
        $response = $objectManager->get(Response::class);
        $request = $objectManager->get(Request::class);

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
                    'blogPost[blog][title]'
                ]
            )
        );

        $referrerRequest = [];
        $referrerRequest['@action'] = 'testForm';
        $request->setArgument(
            '__referrer',
            ['@request' => $this->getHashService()->appendHmac(json_encode($referrerRequest))]
        );

        while (!$request->isDispatched()) {
            try {
                $blogController = $objectManager->get(BlogController::class);
                $blogController->processRequest($request, $response);
            } catch (StopActionException $e) {
            }
        }

        /* @var \TYPO3\CMS\Extbase\Error\Error $titleLengthError */
        $errors = $request->getOriginalRequestMappingResults()->getFlattenedErrors();
        self::assertCount(1, $errors['blog.title']);
        self::assertCount(1, $errors['blog.description']);
        self::assertCount(1, $errors['blogPost.title']);

        self::assertEquals('testFormAction', $response->getContent());
    }

    /**
     * @param array $formFieldNames
     * @return string
     */
    protected function generateTrustedPropertiesToken(array $formFieldNames)
    {
        $mvcPropertyMappingConfigurationService = $this->getObjectManager()->get(
            MvcPropertyMappingConfigurationService::class
        );
        return $mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($formFieldNames, '');
    }

    /**
     * @return HashService
     */
    protected function getHashService()
    {
        return $this->getObjectManager()->get(HashService::class);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
