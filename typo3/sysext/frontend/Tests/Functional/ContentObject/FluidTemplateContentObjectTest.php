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

namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FluidTemplateContentObjectTest extends FunctionalTestCase
{
    use ProphecyTrait;

    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/fluid_test',
    ];

    /**
     * @test
     */
    public function renderWorksWithNestedFluidtemplate(): void
    {
        $tsfe = $this->createMock(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfe;

        $configuration = [
            '10' => 'FLUIDTEMPLATE',
            '10.' => [
                'template' => 'TEXT',
                'template.' => [
                    'value' => 'A{anotherFluidTemplate}C',
                ],
                'variables.' => [
                    'anotherFluidTemplate' => 'FLUIDTEMPLATE',
                    'anotherFluidTemplate.' => [
                        'template' => 'TEXT',
                        'template.' => [
                            'value' => 'B',
                        ],
                    ],
                ],
            ],
        ];
        $expectedResult = 'ABC';

        $contentObjectRenderer = new ContentObjectRenderer($tsfe);
        $contentObjectRenderer->setRequest($this->prophesize(ServerRequestInterface::class)->reveal());
        $fluidTemplateContentObject = $this->get(ContentObjectFactory::class)->getContentObject(
            'COA',
            $this->prophesize(ServerRequestInterface::class)->reveal(),
            $contentObjectRenderer
        );
        $result = $fluidTemplateContentObject->render($configuration);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function renderWorksWithNestedFluidtemplateWithLayouts(): void
    {
        $tsfe = $this->createMock(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfe;

        $configuration = [
            '10' => 'FLUIDTEMPLATE',
            '10.' => [
                'template' => 'TEXT',
                'template.' => [
                    'value' => '<f:layout name="BaseLayout"/><f:section name="main"><f:format.raw>{anotherFluidTemplate}</f:format.raw></f:section>',
                ],
                'layoutRootPaths.' => [
                    '0' => 'EXT:fluid_test/Resources/Private/Layouts',
                ],
                'variables.' => [
                    'anotherFluidTemplate' => 'FLUIDTEMPLATE',
                    'anotherFluidTemplate.' => [
                        'template' => 'TEXT',
                        'template.' => [
                            'value' => '<f:layout name="BaseLayout"/><f:section name="main"></f:section>',
                        ],
                        'layoutRootPaths.' => [
                            '0' => 'EXT:fluid_test/Resources/Private/LayoutOverride/Layouts',
                        ],
                    ],
                ],
            ],
        ];
        $expectedResult = 'DefaultLayoutLayoutOverride';

        $contentObjectRenderer = new ContentObjectRenderer($tsfe);
        $contentObjectRenderer->setRequest($this->prophesize(ServerRequestInterface::class)->reveal());
        $fluidTemplateContentObject = $this->get(ContentObjectFactory::class)->getContentObject(
            'COA',
            $this->prophesize(ServerRequestInterface::class)->reveal(),
            $contentObjectRenderer
        );
        $result = preg_replace('/\s+/', '', strip_tags($fluidTemplateContentObject->render($configuration)));

        self::assertEquals($expectedResult, $result);
    }
}
