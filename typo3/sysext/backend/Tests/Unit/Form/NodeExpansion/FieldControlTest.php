<?php

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

use Prophecy\Argument;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeExpansion\FieldControl;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FieldControlTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderMergesResultOfSingleControls()
    {
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        $iconProphecy = $this->prophesize(Icon::class);
        $iconProphecy->render()->shouldBeCalled()->willReturn('');
        $iconFactoryProphecy->getIcon(Argument::cetera())->shouldBeCalled()->willReturn($iconProphecy->reveal());

        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL(Argument::cetera())->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $nodeFactoryProphecy = $this->prophesize(NodeFactory::class);
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

        $aControlProphecy = $this->prophesize(AbstractNode::class);
        $aControlProphecy->render()->willReturn(
            [
                'iconIdentifier' => 'actions-open',
                'title' => 'aTitle',
                'linkAttributes' => [ 'href' => '' ],
                'additionalJavaScriptPost' => [ 'someJavaScript' ],
                'requireJsModules' => [
                    'aModule',
                ],
            ]
        );
        $aControlNodeFactoryInput = $data;
        $aControlNodeFactoryInput['renderData']['fieldControlOptions'] = [];
        $aControlNodeFactoryInput['renderType'] = 'aControl';
        $nodeFactoryProphecy->create($aControlNodeFactoryInput)->willReturn($aControlProphecy->reveal());

        $anotherControlProphecy = $this->prophesize(AbstractNode::class);
        $anotherControlProphecy->render()->willReturn(
            [
                'iconIdentifier' => 'actions-close',
                'title' => 'aTitle',
                'linkAttributes' => [ 'href' => '' ],
                'requireJsModules' => [
                    'anotherModule',
                ],
            ]
        );
        $anotherControlNodeFactoryInput = $data;
        $anotherControlNodeFactoryInput['renderData']['fieldControlOptions'] = [];
        $anotherControlNodeFactoryInput['renderType'] = 'anotherControl';
        $nodeFactoryProphecy->create($anotherControlNodeFactoryInput)->willReturn($anotherControlProphecy->reveal());

        $expected = [
            'additionalJavaScriptPost' => [
                'someJavaScript',
            ],
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            'requireJsModules' => [
                'aModule',
                'anotherModule',
            ],
            'inlineData' => [],
            'html' => '\n<a class="btn btn-default">\n...>\n</a>'
        ];
        $result = (new FieldControl($nodeFactoryProphecy->reveal(), $data))->render();
        // We're not interested in testing the html merge here
        $expected['html'] = $result['html'];

        self::assertEquals($expected, $result);
    }
}
