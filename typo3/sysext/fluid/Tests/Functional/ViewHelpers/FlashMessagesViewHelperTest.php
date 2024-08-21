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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class FlashMessagesViewHelperTest extends FunctionalTestCase
{
    #[Test]
    public function renderReturnsEmptyStringIfNoFlashMessagesAreInQueue(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = (new Request($serverRequest));
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:flashMessages />');
        $extbaseConfigurationManager = $this->get(ConfigurationManagerInterface::class);
        $extbaseConfigurationManager->setRequest($extbaseRequest);
        self::assertEmpty((new TemplateView($context))->render());
    }

    #[Test]
    public function renderReturnsEmptyStringFromSpecificEmptyQueue(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:flashMessages queueIdentifier="myQueue" />');
        self::assertEmpty((new TemplateView($context))->render());
    }

    #[Test]
    public function renderReturnsRenderedFlashMessage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $flashMessage = new FlashMessage('test message body', 'test message title', ContextualFeedbackSeverity::OK, true);
        (new FlashMessageQueue('myQueue'))->addMessage($flashMessage);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:flashMessages queueIdentifier="myQueue" />');
        // CLI message renderer kicks in with this functional test setup, so no HTML output here.
        self::assertSame('[OK] test message title: test message body', (new TemplateView($context))->render());
    }

    #[Test]
    public function provideFlashMessagesAsVariable(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $flashMessage = new FlashMessage('test message body', 'test message title', ContextualFeedbackSeverity::OK, true);
        (new FlashMessageQueue('myQueue'))->addMessage($flashMessage);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:flashMessages queueIdentifier="myQueue" as="messages">{messages.0.title}</f:flashMessages>');
        // CLI message renderer kicks in with this functional test setup, so no HTML output here.
        self::assertSame('test message title', (new TemplateView($context))->render());
    }

    #[Test]
    public function flashMessagesVariableIsLocal(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $flashMessage = new FlashMessage('test message body', 'test message title', ContextualFeedbackSeverity::OK, true);
        (new FlashMessageQueue('myQueue'))->addMessage($flashMessage);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:flashMessages queueIdentifier="myQueue" as="messages"></f:flashMessages>{messages.0.title}');
        // CLI message renderer kicks in with this functional test setup, so no HTML output here.
        self::assertSame('', (new TemplateView($context))->render());
    }
}
