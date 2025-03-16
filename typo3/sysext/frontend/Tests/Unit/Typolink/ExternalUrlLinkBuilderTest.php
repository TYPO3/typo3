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

namespace TYPO3\CMS\Frontend\Tests\Unit\Typolink;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\ExternalUrlLinkBuilder;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExternalUrlLinkBuilderTest extends UnitTestCase
{
    #[Test]
    public function targetIsAppliedProperly(): void
    {
        $linkDetails = [
            'type' => 'url',
            'url' => 'https://example.com',
        ];
        $subject = $this->prepareSubject();
        $actualResult = $subject->build($linkDetails, '', 'custom-target', []);
        self::assertSame('https://example.com', $actualResult->getLinkText());
        self::assertSame('https://example.com', $actualResult->getUrl());
        self::assertSame('custom-target', $actualResult->getTarget());
    }

    #[Test]
    public function targetFallbackIsAppliedFromExtTargetWithSchemeAndDomain(): void
    {
        $linkDetails = [
            'type' => 'url',
            'url' => 'https://example.com',
        ];
        $subject = $this->prepareSubject();
        $actualResult = $subject->build($linkDetails, '', '', []);
        self::assertSame('externalFallback', $actualResult->getTarget());
    }

    #[Test]
    public function targetFallbackIsAppliedFromExtTargetWithProtocolRelativeDomain(): void
    {
        $linkDetails = [
            'type' => 'url',
            // see https://en.wikipedia.org/wiki/Wikipedia:Protocol-relative_URL
            'url' => '//example.com',
        ];
        $subject = $this->prepareSubject();
        $actualResult = $subject->build($linkDetails, '', '', []);
        self::assertSame('externalFallback', $actualResult->getTarget());
    }
    #[Test]
    public function targetFallbackIsAppliedForAbsolutePath(): void
    {
        $linkDetails = [
            'type' => 'url',
            'url' => '/other-system-like-a-blog-on-the-same-domain',
        ];
        $subject = $this->prepareSubject();
        $actualResult = $subject->build($linkDetails, '', '', []);
        self::assertSame('internalFallback', $actualResult->getTarget());
    }

    private function prepareSubject(): ExternalUrlLinkBuilder&MockObject&AccessibleObjectInterface
    {
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('routing', new PageArguments(1, '', [], [], []));
        $request = $request->withAttribute('frontend.typoscript', new class () {
            public function getConfigArray(): array
            {
                return [
                    'extTarget' => 'externalFallback',
                    'intTarget' => 'internalFallback',
                ];
            }
        });
        $cObj = new ContentObjectRenderer();
        $cObj->setRequest($request);
        $subject = $this->getAccessibleMock(ExternalUrlLinkBuilder::class, null, [], '', false);
        $subject->_set('contentObjectRenderer', $cObj);
        return $subject;
    }
}
