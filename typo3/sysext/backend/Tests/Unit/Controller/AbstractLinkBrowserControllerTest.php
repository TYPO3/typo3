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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Controller\LinkBrowserController;
use TYPO3\CMS\Backend\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractLinkBrowserControllerTest extends UnitTestCase
{
    private LinkBrowserController&MockObject&AccessibleObjectInterface $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(LinkBrowserController::class, ['getLanguageService'], [], '', false);
    }

    #[Test]
    public function getLinkAttributeFieldDefinitionsContainsRelField(): void
    {
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(static fn(string $key): string => $key);
        $this->subject->method('getLanguageService')->willReturn($languageService);
        $this->subject->_set('linkAttributeValues', ['rel' => 'noopener']);

        $result = $this->subject->_call('getLinkAttributeFieldDefinitions');

        self::assertArrayHasKey('rel', $result);
        self::assertStringContainsString('name="lrel"', $result['rel']);
        self::assertStringContainsString('value="noopener"', $result['rel']);
    }

    #[Test]
    public function getAllowedLinkAttributesDoesNotContainRelWhenNotAllowed(): void
    {
        $linkHandler = $this->createMock(LinkHandlerInterface::class);
        $linkHandler->method('getLinkAttributes')->willReturn(['target', 'title', 'class', 'params', 'rel']);
        $this->subject->_set('displayedLinkHandler', $linkHandler);
        $this->subject->_set('parameters', ['params' => ['allowedOptions' => 'target,title,class,params']]);

        $result = $this->subject->_call('getAllowedLinkAttributes');

        self::assertSame(['target', 'title', 'class', 'params'], $result);
        self::assertNotContains('rel', $result);
    }
}
