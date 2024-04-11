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

namespace TYPO3\CMS\Extbase\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Exception\Crypto\InvalidHashStringException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\Argument;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\FileHandlingService;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\FileUpload\Domain\Model\FileReferencePropertySingle;
use TYPO3Tests\FileUpload\Domain\Model\FileUploadMultipleProperties;

final class FileHandlingServiceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/file_upload'];

    protected FileHandlingService $fileHandlingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileHandlingService = $this->getContainer()->get(FileHandlingService::class);
    }

    #[Test]
    public function noFileUploadConfigurationIsInitializedIfRequestMethodIsNotPost(): void
    {
        $validator = GeneralUtility::makeInstance(ConjunctionValidator::class);

        $argument = new Argument('fileUploadSingleFileReference', FileReferencePropertySingle::class);
        $argument->setValidator($validator);

        $arguments = new Arguments();
        $arguments->addArgument($argument);

        $serverRequest = (new ServerRequest('/some/uri', 'GET'))
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = new Request($serverRequest);

        $this->fileHandlingService->initializeFileUploadConfigurationsFromRequest($request, $arguments);

        $fileUploadPropertiesArgument = $arguments->getArgument('fileUploadSingleFileReference');
        self::assertEmpty($fileUploadPropertiesArgument->getFileHandlingServiceConfiguration()->getFileUploadConfigurations());
    }

    #[Test]
    public function noFileUploadConfigurationIsInitializedForArgumentWithNoValidator(): void
    {
        $argument = new Argument('fileUploadSingleFileReference', FileReferencePropertySingle::class);

        $arguments = new Arguments();
        $arguments->addArgument($argument);

        $serverRequest = (new ServerRequest('/some/uri', 'POST'))
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = new Request($serverRequest);

        $this->fileHandlingService->initializeFileUploadConfigurationsFromRequest($request, $arguments);

        $fileUploadPropertiesArgument = $arguments->getArgument('fileUploadSingleFileReference');
        self::assertEmpty($fileUploadPropertiesArgument->getFileHandlingServiceConfiguration()->getFileUploadConfigurations());
    }

    #[Test]
    public function fileUploadConfigurationIsInitializedForArgumentWithSingleFileUploadProperty(): void
    {
        $argument = new Argument('fileUploadSingleFileReference', FileReferencePropertySingle::class);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $arguments = new Arguments();
        $arguments->addArgument($argument);

        $serverRequest = (new ServerRequest('/some/uri', 'POST'))
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = new Request($serverRequest);

        $this->fileHandlingService->initializeFileUploadConfigurationsFromRequest($request, $arguments);

        $fileUploadPropertiesArgument = $arguments->getArgument('fileUploadSingleFileReference');
        $fileUploadConfigurations = $fileUploadPropertiesArgument->getFileHandlingServiceConfiguration()
            ->getFileUploadConfigurations();
        $fileUploadConfigurationForProperty = $fileUploadPropertiesArgument->getFileHandlingServiceConfiguration()
            ->getFileUploadConfigurationForProperty('file');
        self::assertCount(1, $fileUploadConfigurations);
        self::assertNotEmpty($fileUploadConfigurationForProperty);
    }

    #[Test]
    public function multipleFileUploadConfigurationsAreInitializedForArgumentWithMultipleFileUploadProperties(): void
    {
        $argument = new Argument('fileUploadMultipleProperties', FileUploadMultipleProperties::class);

        $validationResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
        $validator = $validationResolver->createValidator(ConjunctionValidator::class);
        $argument->setValidator($validator);

        $arguments = new Arguments();
        $arguments->addArgument($argument);

        $serverRequest = (new ServerRequest('/some/uri', 'POST'))
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = new Request($serverRequest);

        $this->fileHandlingService->initializeFileUploadConfigurationsFromRequest($request, $arguments);

        $fileUploadPropertiesArgument = $arguments->getArgument('fileUploadMultipleProperties');
        $fileUploadConfigurations = $fileUploadPropertiesArgument->getFileHandlingServiceConfiguration()
            ->getFileUploadConfigurations();
        self::assertCount(2, $fileUploadConfigurations);
    }

    #[Test]
    public function noFileUploadDeletionConfigurationIsInitializedIfRequestMethodIsNotPost(): void
    {
        $validator = GeneralUtility::makeInstance(ConjunctionValidator::class);

        $argument = new Argument('fileUploadSingleFileReference', FileReferencePropertySingle::class);
        $argument->setValidator($validator);

        $arguments = new Arguments();
        $arguments->addArgument($argument);

        $serverRequest = (new ServerRequest('/some/uri', 'GET'))
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = new Request($serverRequest);

        $this->fileHandlingService->initializeFileUploadDeletionConfigurationsFromRequest($request, $arguments);

        $fileUploadPropertiesArgument = $arguments->getArgument('fileUploadSingleFileReference');
        self::assertEmpty($fileUploadPropertiesArgument->getFileHandlingServiceConfiguration()->getFileUploadDeletionConfigurations());
    }

    #[Test]
    public function noFileUploadDeletionConfigurationIsInitializedIfRequestMethodIsNoDeletionDataSubmitted(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupTree(new RootNode());
        $frontendTypoScript->setSetupArray([]);

        $validator = GeneralUtility::makeInstance(ConjunctionValidator::class);

        $argument = new Argument('fileUploadSingleFileReference', FileReferencePropertySingle::class);
        $argument->setValidator($validator);

        $arguments = new Arguments();
        $arguments->addArgument($argument);

        $extbaseRequestParamaters = new ExtbaseRequestParameters();
        $extbaseRequestParamaters->setControllerExtensionName('MyExtension');
        $extbaseRequestParamaters->setPluginName('myPlugin');

        $parsedBody = [];

        $serverRequest = (new ServerRequest('/some/uri', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParamaters)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withParsedBody($parsedBody)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = new Request($serverRequest);

        $configurationManager = $this->get(ConfigurationManagerInterface::class);
        $configurationManager->setRequest($request);

        $this->fileHandlingService->initializeFileUploadDeletionConfigurationsFromRequest($request, $arguments);

        $fileUploadPropertiesArgument = $arguments->getArgument('fileUploadSingleFileReference');
        self::assertEmpty($fileUploadPropertiesArgument->getFileHandlingServiceConfiguration()->getFileUploadDeletionConfigurations());
    }

    #[Test]
    public function noFileUploadDeletionConfigurationIsInitializedIfRequestMethodIsNoDeletionDataSubmitted1(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupTree(new RootNode());
        $frontendTypoScript->setSetupArray([]);

        $validator = GeneralUtility::makeInstance(ConjunctionValidator::class);

        $argument = new Argument('fileUploadSingleFileReference', FileReferencePropertySingle::class);
        $argument->setValidator($validator);

        $arguments = new Arguments();
        $arguments->addArgument($argument);

        $extbaseRequestParamaters = new ExtbaseRequestParameters();
        $extbaseRequestParamaters->setControllerExtensionName('MyExtension');
        $extbaseRequestParamaters->setPluginName('myPlugin');

        $parsedBody = [
            'tx_myextension_myplugin' => [
                FileHandlingService::DELETE_IDENTIFIER => [
                    'fileUploadSingleFileReference' => [
                        'property-hash' => 'wrong-signed-value',
                    ],
                ],
            ],
        ];

        $serverRequest = (new ServerRequest('/some/uri', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParamaters)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withParsedBody($parsedBody)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = new Request($serverRequest);

        $configurationManager = $this->get(ConfigurationManagerInterface::class);
        $configurationManager->setRequest($request);

        $this->expectException(InvalidHashStringException::class);
        $this->fileHandlingService->initializeFileUploadDeletionConfigurationsFromRequest($request, $arguments);
    }

    // @todo Add more tests
    // - more tests for data integrity in initializefileUploadDeletionConfigurationsFromRequest
    // - mapUploadedFilesToArgument including persistence of file(s)
}
