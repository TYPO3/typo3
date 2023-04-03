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

use ExtbaseTeam\ActionControllerTest\Controller\TestController;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Validation\Validator\CustomValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView as FluidTemplateView;

final class ActionControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Mvc/Controller/Fixture/Extension/action_controller_test',
    ];

    /**
     * @test
     */
    public function initializeActionMethodArgumentsRegistersArgumentsFoundInTheSignatureOfTheCurrentActionMethod(): void
    {
        $subject = $this->get(TestController::class);
        $subject->arguments = new Arguments();
        $subject->actionMethodName = 'initializeActionMethodArgumentsTestActionOne';
        $subject->initializeActionMethodArguments();

        self::assertSame('string', $subject->arguments['stringArgument']->getDataType());
        self::assertTrue($subject->arguments['stringArgument']->isRequired());
        self::assertNull($subject->arguments['stringArgument']->getDefaultValue());

        self::assertSame('integer', $subject->arguments['integerArgument']->getDataType());
        self::assertTrue($subject->arguments['integerArgument']->isRequired());
        self::assertNull($subject->arguments['integerArgument']->getDefaultValue());

        self::assertSame('stdClass', $subject->arguments['objectArgument']->getDataType());
        self::assertTrue($subject->arguments['objectArgument']->isRequired());
        self::assertNull($subject->arguments['objectArgument']->getDefaultValue());
    }

    /**
     * @test
     */
    public function initializeActionMethodArgumentsRegistersOptionalArgumentsAsSuch(): void
    {
        $subject = $this->get(TestController::class);
        $subject->arguments = new Arguments();
        $subject->actionMethodName = 'initializeActionMethodArgumentsTestActionTwo';
        $subject->initializeActionMethodArguments();

        self::assertSame('string', $subject->arguments['arg1']->getDataType());
        self::assertTrue($subject->arguments['arg1']->isRequired());
        self::assertNull($subject->arguments['arg1']->getDefaultValue());

        self::assertSame('array', $subject->arguments['arg2']->getDataType());
        self::assertFalse($subject->arguments['arg2']->isRequired());
        self::assertSame([21], $subject->arguments['arg2']->getDefaultValue());

        self::assertSame('string', $subject->arguments['arg3']->getDataType());
        self::assertFalse($subject->arguments['arg3']->isRequired());
        self::assertSame('foo', $subject->arguments['arg3']->getDefaultValue());
    }

    /**
     * @test
     */
    public function initializeActionMethodArgumentsThrowsExceptionIfDataTypeWasNotSpecified(): void
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $this->expectExceptionCode(1253175643);
        $subject = $this->get(TestController::class);
        $subject->arguments = new Arguments();
        $subject->actionMethodName = 'initializeActionMethodArgumentsTestActionThree';
        $subject->initializeActionMethodArguments();
    }

    /**
     * @test
     */
    public function processRequestThrowsAnExceptionIfTheActionDefinedInTheRequestDoesNotExist(): void
    {
        $this->expectException(NoSuchActionException::class);
        $this->expectExceptionCode(1186669086);
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('Test')
            ->withControllerActionName('doesNotExist');
        $subject = $this->get(TestController::class);
        $subject->processRequest($request);
    }

    /**
     * @test
     */
    public function processRequestSetsActionMethodName(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('Test')
            ->withControllerActionName('qux');
        $subject = $this->get(TestController::class);
        $subject->processRequest($request);
        self::assertSame('quxAction', $subject->actionMethodName);
    }

    /**
     * @test
     */
    public function customValidatorsAreProperlyResolved(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('Test')
            ->withControllerActionName('bar')
            ->withArgument('barParam', '');

        $subject = $this->get(TestController::class);
        $subject->processRequest($request);

        $arguments = $subject->getArguments();
        $argument = $arguments->getArgument('barParam');

        $conjunctionValidator = $argument->getValidator();
        self::assertInstanceOf(ConjunctionValidator::class, $conjunctionValidator);
        $validators = $conjunctionValidator->getValidators();
        self::assertInstanceOf(\SplObjectStorage::class, $validators);
        $validators->rewind();
        self::assertInstanceOf(CustomValidator::class, $validators->current());
    }

    /**
     * @test
     */
    public function extbaseValidatorsAreProperlyResolved(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('Test')
            ->withControllerActionName('baz')
            ->withArgument('bazParam', [ 'notEmpty' ]);

        $subject = $this->get(TestController::class);
        $subject->processRequest($request);

        $arguments = $subject->getArguments();
        $argument = $arguments->getArgument('bazParam');

        $conjunctionValidator = $argument->getValidator();
        self::assertInstanceOf(ConjunctionValidator::class, $conjunctionValidator);
        $validators = $conjunctionValidator->getValidators();
        self::assertInstanceOf(\SplObjectStorage::class, $validators);
        self::assertCount(1, $validators);
        $validators->rewind();
        self::assertInstanceOf(NotEmptyValidator::class, $validators->current());
    }

    /**
     * @test
     */
    public function resolveViewRespectsDefaultViewObjectName(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('Test')
            ->withControllerActionName('qux');

        $subject = $this->get(TestController::class);

        $reflectionClass = new \ReflectionClass($subject);
        $reflectionMethod = $reflectionClass->getProperty('defaultViewObjectName');
        $reflectionMethod->setValue($subject, JsonView::class);

        $subject->processRequest($request);

        $reflectionMethod = $reflectionClass->getProperty('view');
        $view = $reflectionMethod->getValue($subject);
        self::assertInstanceOf(JsonView::class, $view);
    }

    /**
     * @test
     */
    public function setViewConfigurationConfiguresViewWithArray(): void
    {
        $configurationManagerMock = $this->createMock(ConfigurationManager::class);
        $configurationManagerMock->method('getConfiguration')->willReturn(
            [
                'view' => [
                    'templateRootPaths' => ['a template path'],
                    'layoutRootPaths' => ['a layout path'],
                    'partialRootPaths' => ['a partial path'],
                ],
            ],
        );

        $viewMock = $this->createMock(TemplateView::class);
        $viewMock->expects(self::once())->method('setTemplateRootPaths')->with(['a template path']);
        $viewMock->expects(self::once())->method('setLayoutRootPaths')->with(['a layout path']);
        $viewMock->expects(self::once())->method('setPartialRootPaths')->with(['a partial path']);

        $subject = $this->get(TestController::class);
        $subject->injectConfigurationManager($configurationManagerMock);
        $subject->setViewConfiguration($viewMock);
    }

    /**
     * @test
     */
    public function setViewConfigurationDoesNotCallSettersWithEmptyArray(): void
    {
        $configurationManagerMock = $this->createMock(ConfigurationManager::class);
        $configurationManagerMock->method('getConfiguration')->willReturn(
            [
                'view' => [
                    'templateRootPaths' => [],
                    'layoutRootPaths' => [],
                    'partialRootPaths' => [],
                ],
            ],
        );

        $viewMock = $this->createMock(TemplateView::class);
        $viewMock->expects(self::never())->method('setTemplateRootPaths');
        $viewMock->expects(self::never())->method('setLayoutRootPaths');
        $viewMock->expects(self::never())->method('setPartialRootPaths');

        $subject = $this->get(TestController::class);
        $subject->injectConfigurationManager($configurationManagerMock);
        $subject->setViewConfiguration($viewMock);
    }

    /**
     * @test
     */
    public function renderAssetsForRequestAssignsHeaderDataFromViewIntoPageRenderer(): void
    {
        $viewMock = $this->createMock(FluidTemplateView::class);
        $viewMock->expects(self::exactly(2))->method('renderSection')->willReturnOnConsecutiveCalls('custom-header-data', '');
        $expectedHeader = 'custom-header-data';

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::atLeastOnce())->method('addHeaderData')->with($expectedHeader);
        $pageRenderer->expects(self::never())->method('addFooterData');
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer);

        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($serverRequest));

        $subject = $this->get(TestController::class);
        $subject->view = $viewMock;
        $subject->renderAssetsForRequest($request);
    }

    /**
     * @test
     */
    public function renderAssetsForRequestAssignsFooterDataFromViewIntoPageRenderer(): void
    {
        $viewMock = $this->createMock(FluidTemplateView::class);
        $viewMock->expects(self::exactly(2))->method('renderSection')->willReturnOnConsecutiveCalls('', 'custom-footer-data');
        $expectedFooter = 'custom-footer-data';

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer->expects(self::never())->method('addHeaderData');
        $pageRenderer->expects(self::atLeastOnce())->method('addFooterData')->with($expectedFooter);
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer);

        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($serverRequest));

        $subject = $this->get(TestController::class);
        $subject->view = $viewMock;
        $subject->renderAssetsForRequest($request);
    }

    /**
     * @test
     */
    public function addFlashMessageAddsFlashMessageToFlashMessageQueue(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('Test')
            ->withControllerActionName('bar')
            ->withPluginName('Pi1');

        $messageBody = 'Message body';
        $messageTitle = 'Message title';
        $messageSeverity = ContextualFeedbackSeverity::OK;

        $subject = $this->get(TestController::class);
        $subject->request = $request;
        $subject->addFlashMessage($messageBody, $messageTitle, $messageSeverity, false);

        $queue = $subject->getFlashMessageQueue();
        self::assertSame('extbase.flashmessages.tx_actioncontrollertest_pi1', $queue->getIdentifier());
        $messages = $queue->getAllMessages();
        self::assertCount(1, $messages);
        self::assertSame($messageBody, $messages[0]->getMessage());
        self::assertSame($messageTitle, $messages[0]->getTitle());
        self::assertSame($messageSeverity, $messages[0]->getSeverity());
    }
}
