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
use TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkSoftReferenceParser;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TypolinkSoftReferenceParserTest extends FunctionalTestCase
{
    #[Test]
    public function appendLinkHandlerElementsEventIsTriggeredForUnknownLinkTypes(): void
    {
        $eventFired = false;
        $originalContent = '';

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'test-append-link-handler-elements-listener',
            static function (AppendLinkHandlerElementsEvent $event) use (&$eventFired, &$originalContent) {
                $eventFired = true;
                $originalContent = $event->getContent();
                $event->addElements([
                    $event->getTokenId() . ':0' => [
                        'matchString' => 'customhandler:123',
                        'subst' => [
                            'type' => 'db',
                            'recordRef' => 'tx_example:123',
                            'tokenID' => $event->getTokenId(),
                            'tokenValue' => 'customhandler:123',
                        ],
                    ],
                ]);
                $event->setContent('{softref:' . $event->getTokenId() . '}');
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AppendLinkHandlerElementsEvent::class, 'test-append-link-handler-elements-listener');

        $result = $this->get(TypolinkSoftReferenceParser::class)->parse('tt_content', 'bodytext', 1, 'customhandler:123');

        self::assertTrue($eventFired);
        self::assertEquals('customhandler:123', $originalContent);
        self::assertTrue($result->hasMatched());

        $customElement = null;
        foreach ($result->getMatchedElements() as $element) {
            if ($element['matchString'] === 'customhandler:123') {
                $customElement = $element;
                break;
            }
        }

        self::assertNotNull($customElement);
        self::assertEquals('db', $customElement['subst']['type']);
        self::assertEquals('tx_example:123', $customElement['subst']['recordRef']);
        self::assertEquals('customhandler:123', $customElement['subst']['tokenValue']);
        self::assertStringContainsString('{softref:', $result->getContent());
    }

    #[Test]
    public function appendLinkHandlerElementsEventAddsErrorWhenNotResolved(): void
    {
        $eventFired = false;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'test-append-link-handler-elements-no-resolve-listener',
            static function (AppendLinkHandlerElementsEvent $event) use (&$eventFired) {
                $eventFired = true;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AppendLinkHandlerElementsEvent::class, 'test-append-link-handler-elements-no-resolve-listener');

        $result = $this->get(TypolinkSoftReferenceParser::class)->parse('tt_content', 'bodytext', 1, 'customhandler:123');

        self::assertTrue($eventFired, 'AppendLinkHandlerElementsEvent should have been dispatched');
        self::assertTrue($result->hasMatched());

        $errorElement = null;
        foreach ($result->getMatchedElements() as $element) {
            if (isset($element['error'])) {
                $errorElement = $element;
                break;
            }
        }

        self::assertNotNull($errorElement);
        self::assertEquals('Couldn\'t decide typolink mode.', $errorElement['error']);
    }
}
