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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeExpansion\FieldControl;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FieldControlTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderMergesResultOfSingleControls(): void
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryMock);
        $iconMock = $this->createMock(Icon::class);
        $iconMock->expects(self::atLeastOnce())->method('render')->willReturn('');
        $iconFactoryMock->expects(self::atLeastOnce())->method('getIcon')->with(self::anything())
            ->willReturn($iconMock);

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
                'additionalJavaScriptPost' => [ 'someJavaScript' ],
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

        $nodeFactoryMock->method('create')->withConsecutive([$aControlNodeFactoryInput], [$anotherControlNodeFactoryInput])
            ->willReturnOnConsecutiveCalls($aControlMock, $anotherControlMock);

        $expected = [
            'additionalJavaScriptPost' => [
                'someJavaScript',
            ],
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'javaScriptModules' => [
                'aModule',
                'anotherModule',
            ],
            /** @deprecated will be removed in TYPO3 v13.0 */
            'requireJsModules' => [],
            'inlineData' => [],
            'html' => '\n<a class="btn btn-default">\n...>\n</a>',
        ];
        $result = (new FieldControl($nodeFactoryMock, $data))->render();
        // We're not interested in testing the html merge here
        $expected['html'] = $result['html'];

        self::assertEquals($expected, $result);
    }
}
