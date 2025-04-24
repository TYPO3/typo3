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

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Validation\Validator\CustomValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView as FluidTemplateView;
use TYPO3Tests\ActionControllerTest\Controller\TestController;
use TYPO3Tests\ActionControllerTest\Domain\Model\Model;

final class ActionControllerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/action_controller_test',
    ];

    #[Test]
    public function initializeActionMethodArgumentsRegistersArgumentsFoundInTheSignatureOfTheCurrentActionMethod(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
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

    #[Test]
    public function initializeActionMethodArgumentsRegistersOptionalArgumentsAsSuch(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
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

    #[Test]
    public function initializeActionMethodArgumentsThrowsExceptionIfDataTypeWasNotSpecified(): void
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $this->expectExceptionCode(1253175643);
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
        $subject = $this->get(TestController::class);
        $subject->arguments = new Arguments();
        $subject->actionMethodName = 'initializeActionMethodArgumentsTestActionThree';
        $subject->initializeActionMethodArguments();
    }

    #[Test]
    public function processRequestThrowsAnExceptionIfTheActionDefinedInTheRequestDoesNotExist(): void
    {
        $this->expectException(NoSuchActionException::class);
        $this->expectExceptionCode(1186669086);
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('Test')
            ->withControllerActionName('doesNotExist');
        $subject = $this->get(TestController::class);
        $subject->processRequest($request);
    }

    #[Test]
    public function processRequestSetsActionMethodName(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('Test')
            ->withControllerActionName('qux');
        $subject = $this->get(TestController::class);
        $subject->processRequest($request);
        self::assertSame('quxAction', $subject->actionMethodName);
    }

    #[Test]
    public function customValidatorsAreProperlyResolved(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
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
        self::assertInstanceOf(ServerRequestInterface::class, $validators->current()->getRequest());
    }

    #[Test]
    public function extbaseValidatorsAreProperlyResolved(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
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
        self::assertInstanceOf(ServerRequestInterface::class, $validators->current()->getRequest());
    }

    #[Test]
    public function resolveViewRespectsDefaultViewObjectName(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
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

    #[Test]
    public function renderAssetsForRequestAssignsHeaderDataFromViewIntoPageRenderer(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

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

    #[Test]
    public function renderAssetsForRequestAssignsFooterDataFromViewIntoPageRenderer(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

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

    #[Test]
    public function addFlashMessageAddsFlashMessageToFlashMessageQueue(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

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

    #[Test]
    public function mapRequestArgumentsToControllerArgumentsMapsUploadedFilesToArgument(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        $testFilename = $this->createTestFile('testfile.txt', 'TYPO3 - Inspiring People To Share');
        $uploadedFile = new UploadedFile($testFilename, 100, UPLOAD_ERR_OK, 'testfile.txt');

        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setUploadedFiles(['fooParam' => [$uploadedFile]]);

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('Test')
            ->withControllerActionName('bar')
            ->withPluginName('Pi1')
            ->withArgument('fooParam', new Model());

        $subject = $this->get(TestController::class);
        $subject->arguments = new Arguments();
        $subject->actionMethodName = 'fooAction';
        $subject->request = $request;
        $subject->initializeActionMethodArguments();
        $subject->mapRequestArgumentsToControllerArguments();

        self::assertSame([$uploadedFile], $subject->arguments['fooParam']->getUploadedFiles());
    }

    /**
     * Helper function to create a test file with the given content.
     */
    protected function createTestFile(string $filename, string $content): string
    {
        $path = $this->instancePath . '/tmp';
        $testFilename = $path . $filename;

        GeneralUtility::mkdir($path);
        touch($testFilename);
        file_put_contents($testFilename, $content);

        return $testFilename;
    }
}
