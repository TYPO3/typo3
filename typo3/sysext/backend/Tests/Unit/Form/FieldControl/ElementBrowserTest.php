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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FieldControl;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Form\FieldControl\ElementBrowser;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ElementBrowserTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function renderTrimsAllowedValuesFromConfigSection(): void
    {
        $nodeFactory = $this->prophesize(NodeFactory::class);
        $elementBrowser = new ElementBrowser($nodeFactory->reveal(), [
            'fieldName' => 'somefield',
            'isInlineChild' => false,
            'tableName' => 'tt_content',
            'inlineStructure' => [],
            'parameterArray' => [
                'itemFormElName' => '',
                'fieldConf' => [
                    'config' => [
                        'allowed' => 'be_users, be_groups',
                    ],
                ],
            ],
        ]);

        $result = $elementBrowser->render();
        self::assertSame($result['linkAttributes']['data-params'], '|||be_users,be_groups|');
    }

    /**
     * @test
     */
    public function renderTrimsAllowedValuesFromAppearanceSection(): void
    {
        $nodeFactory = $this->prophesize(NodeFactory::class);
        $elementBrowser = new ElementBrowser($nodeFactory->reveal(), [
            'fieldName' => 'somefield',
            'isInlineChild' => false,
            'tableName' => 'tt_content',
            'inlineStructure' => [],
            'parameterArray' => [
                'itemFormElName' => '',
                'fieldConf' => [
                    'config' => [
                        'appearance' => [
                            'elementBrowserAllowed' => 'be_users, be_groups',
                        ],
                    ],
                ],
            ],
        ]);
        $result = $elementBrowser->render();
        self::assertSame($result['linkAttributes']['data-params'], '|||be_users,be_groups|');
    }
}
