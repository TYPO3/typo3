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

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class FlashMessagesViewHelperTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function renderReturnsEmptyStringIfNoFlashMessagesAreInQueue(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:flashMessages />');
        $context->setRequest(new Request());
        self::assertEmpty((new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringFromSpecificEmptyQueue(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:flashMessages queueIdentifier="myQueue" />');
        self::assertEmpty((new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderReturnsRenderedFlashMessage(): void
    {
        $this->setUpBackendUserFromFixture(1);
        $flashMessage = new FlashMessage('test message body', 'test message title', AbstractMessage::OK, true);
        (new FlashMessageQueue('myQueue'))->addMessage($flashMessage);
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:flashMessages queueIdentifier="myQueue" />');
        // CLI message renderer kicks in with this functional test setup, so no HTML output here.
        self::assertSame('[SUCCESS] test message title: test message body', (new TemplateView($context))->render());
    }
}
