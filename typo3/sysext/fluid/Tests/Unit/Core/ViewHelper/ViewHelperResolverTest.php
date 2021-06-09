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

namespace TYPO3\CMS\Fluid\Tests\Unit\Core\ViewHelper;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper;
use TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlentitiesViewHelper;
use TYPO3\CMS\Fluid\ViewHelpers\RenderViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ViewHelperResolverTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider getResolveViewHelperNameTestValues
     * @param string $namespace
     * @param string $method
     * @param string $expected
     */
    public function resolveViewHelperClassNameResolvesExpectedViewHelperClassName($namespace, $method, $expected)
    {
        $viewHelperResolver = new ViewHelperResolver(
            $this->prophesize(ContainerInterface::class)->reveal(),
            $this->prophesize(ObjectManagerInterface::class)->reveal(),
            [
                'f' => [
                    0 => 'TYPO3Fluid\Fluid\ViewHelpers',
                    1 => 'TYPO3\CMS\Fluid\ViewHelpers',
                ]
            ]
        );
        self::assertEquals($expected, $viewHelperResolver->resolveViewHelperClassName($namespace, $method));
    }

    /**
     * @return array
     */
    public function getResolveViewHelperNameTestValues()
    {
        return [
            ['f', 'cObject', CObjectViewHelper::class],
            ['f', 'format.htmlentities', HtmlentitiesViewHelper::class],
            ['f', 'render', RenderViewHelper::class]
        ];
    }
}
