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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\SoftReference;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\DataHandling\Event\AppendLinkHandlerElementsEvent;
use TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkTagSoftReferenceParser;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TypolinkTagSoftReferenceParserTest extends FunctionalTestCase
{
    #[Test]
    public function appendLinkHandlerElementsEventIsTriggeredForUnknownLinkTypes(): void
    {
        $eventFired = false;
        $originalContent = '';

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'test-append-link-handler-elements-tag-listener',
            static function (AppendLinkHandlerElementsEvent $event) use (&$eventFired, &$originalContent) {
                $eventFired = true;
                $originalContent = $event->getContent();
                $event->addElements([
                    $event->getIdx() => [
                        'matchString' => '<a href="customhandler:123">',
                        'subst' => [
                            'type' => 'db',
                            'recordRef' => 'tx_example:123',
                            'tokenID' => $event->getTokenId(),
                            'tokenValue' => 'customhandler:123',
                        ],
                    ],
                ]);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AppendLinkHandlerElementsEvent::class, 'test-append-link-handler-elements-tag-listener');

        $content = '<p><a href="customhandler:123">Click here</a></p>';
        $result = $this->get(TypolinkTagSoftReferenceParser::class)->parse('tt_content', 'bodytext', 1, $content);

        self::assertTrue($eventFired);
        self::assertEquals($content, $originalContent);
        self::assertTrue($result->hasMatched());

        $customElement = null;
        foreach ($result->getMatchedElements() as $element) {
            if ($element['matchString'] === '<a href="customhandler:123">') {
                $customElement = $element;
                break;
            }
        }

        self::assertNotNull($customElement);
        self::assertEquals('db', $customElement['subst']['type']);
        self::assertEquals('tx_example:123', $customElement['subst']['recordRef']);
        self::assertEquals('customhandler:123', $customElement['subst']['tokenValue']);
        self::assertStringContainsString('<p><a href=', $result->getContent());
    }

    #[Test]
    public function appendLinkHandlerElementsEventIsSkippedWhenNotResolved(): void
    {
        $eventFired = false;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'test-append-link-handler-elements-tag-no-resolve-listener',
            static function (AppendLinkHandlerElementsEvent $event) use (&$eventFired) {
                $eventFired = true;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AppendLinkHandlerElementsEvent::class, 'test-append-link-handler-elements-tag-no-resolve-listener');

        $content = '<p><a href="customhandler:123">Click here</a></p>';
        $result = $this->get(TypolinkTagSoftReferenceParser::class)->parse('tt_content', 'bodytext', 1, $content);

        self::assertTrue($eventFired);
        self::assertFalse($result->hasMatched());
    }
}
