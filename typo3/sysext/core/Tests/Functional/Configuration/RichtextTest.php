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

namespace TYPO3\CMS\Core\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Configuration\Event\AfterRichtextConfigurationPreparedEvent;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

use function PHPUnit\Framework\assertEquals;

final class RichtextTest extends FunctionalTestCase
{
    #[Test]
    public function afterRichtextConfigurationPreparedEventIsCalled(): void
    {
        $afterRichtextConfigurationPreparedEvent = null;
        $fieldConfig = [
            'type' => 'text',
            'enableRichtext' => true,
        ];
        $pageTsConfig = [
            'classes.' => [
                'aClass' => 'aConfig',
            ],
            'default.' => [
                'removeComments' => '1',
            ],
            'config.' => [
                'aTable.' => [
                    'aField.' => [
                        'types.' => [
                            'textmedia.' => [
                                'proc.' => [
                                    'overruleMode' => 'myTransformation',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'editor' => [
                'config' => [
                    'debug' => true,
                ],
            ],
        ];

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-richtext-configuration-prepared-listener',
            static function (AfterRichtextConfigurationPreparedEvent $event) use (&$afterRichtextConfigurationPreparedEvent, $expected): void {
                $afterRichtextConfigurationPreparedEvent = $event;
                $event->setConfiguration($expected);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterRichtextConfigurationPreparedEvent::class, 'after-richtext-configuration-prepared-listener');

        $subject = $this->getAccessibleMock(Richtext::class, ['getRtePageTsConfigOfPid'], [], '', false);
        $subject->expects(self::once())->method('getRtePageTsConfigOfPid')->with(42)->willReturn($pageTsConfig);
        $output = $subject->getConfiguration('aTable', 'aField', 42, 'textmedia', $fieldConfig);

        self::assertInstanceOf(AfterRichtextConfigurationPreparedEvent::class, $afterRichtextConfigurationPreparedEvent);
        self:assertEquals($expected, $output);
    }
}
