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

namespace TYPO3\CMS\Backend\Tests\Unit\Routing;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Routing\Event\BeforePagePreviewUriGeneratedEvent;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PreviewUriBuilderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @test
     */
    public function attributesContainAlternativeUri(): void
    {
        $eventDispatcher = new class () implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                if ($event instanceof BeforePagePreviewUriGeneratedEvent) {
                    $alternativeUri = 'https://typo3.org/about/typo3-the-cms/the-history-of-typo3/#section';
                    $event->setPreviewUri(new Uri($alternativeUri));
                }
                return $event;
            }
        };
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);
        $subject = PreviewUriBuilder::create(0)->withModuleLoading(false);
        $attributes = $subject->buildDispatcherAttributes([PreviewUriBuilder::OPTION_SWITCH_FOCUS => false]);

        self::assertSame(
            [
                'data-dispatch-action' => 'TYPO3.WindowManager.localOpen',
                'data-dispatch-args' => '["https:\/\/typo3.org\/about\/typo3-the-cms\/the-history-of-typo3\/#section",false,"newTYPO3frontendWindow"]',
            ],
            $attributes
        );
    }
}
