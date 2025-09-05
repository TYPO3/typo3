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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\NodeExpansion;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeExpansion\FieldControl;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\NodeInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FieldControlTest extends UnitTestCase
{
    #[Test]
    public function renderMergesResultOfSingleControls(): void
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconMock = $this->createMock(Icon::class);
        $iconMock->expects($this->atLeastOnce())->method('setTitle')->willReturn($iconMock);
        $iconMock->expects($this->atLeastOnce())->method('render')->willReturn('');
        $iconFactoryMock->expects($this->atLeastOnce())->method('getIcon')->with(self::anything())->willReturn($iconMock);

        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceMock->method('sL')->with(self::anything())->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceMock;

        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $data = [
            'renderData' => [
                'fieldControl' => [
                    'aControl' => [
                        'renderType' => 'aControl',
                    ],
                    'anotherControl' => [
                        'renderType' => 'anotherControl',
                        'after' => [ 'aControl' ],
                    ],
                ],
            ],
        ];

        $aControlMock = $this->createMock(AbstractNode::class);
        $aControlMock->method('render')->willReturn(
            [
                'iconIdentifier' => 'actions-open',
                'title' => 'aTitle',
                'linkAttributes' => [ 'href' => '' ],
                'javaScriptModules' => [
                    'aModule',
                ],
            ]
        );
        $aControlNodeFactoryInput = $data;
        $aControlNodeFactoryInput['renderData']['fieldControlOptions'] = [];
        $aControlNodeFactoryInput['renderType'] = 'aControl';

        $anotherControlMock = $this->createMock(AbstractNode::class);
        $anotherControlMock->method('render')->willReturn(
            [
                'iconIdentifier' => 'actions-close',
                'title' => 'aTitle',
                'linkAttributes' => [ 'href' => '' ],
                'javaScriptModules' => [
                    'anotherModule',
                ],
            ]
        );
        $anotherControlNodeFactoryInput = $data;
        $anotherControlNodeFactoryInput['renderData']['fieldControlOptions'] = [];
        $anotherControlNodeFactoryInput['renderType'] = 'anotherControl';

        $series = [
            [$aControlNodeFactoryInput, $aControlMock],
            [$anotherControlNodeFactoryInput, $anotherControlMock],
        ];
        $nodeFactoryMock->method('create')->willReturnCallback(function (array $data) use (&$series): NodeInterface {
            [$expectedArgs, $return] = array_shift($series);
            self::assertSame($expectedArgs, $data);
            return $return;
        });

        $expected = [
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [
                'aModule',
                'anotherModule',
            ],
            'inlineData' => [],
            'html' => '\n<a class="btn btn-default">\n...>\n</a>',
        ];
        $subject = new FieldControl($nodeFactoryMock, $iconFactoryMock, new DependencyOrderingService());
        $subject->setData($data);
        $result = $subject->render();
        // We're not interested in testing the html merge here
        $expected['html'] = $result['html'];

        self::assertEquals($expected, $result);
    }
}
