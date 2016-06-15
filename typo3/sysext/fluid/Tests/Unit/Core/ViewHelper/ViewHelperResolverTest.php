<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\ViewHelper;

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
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper;
use TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlentitiesViewHelper;
use TYPO3Fluid\Fluid\ViewHelpers\RenderViewHelper;

/**
 * Test case
 */
class ViewHelperResolverTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function createViewHelperInstanceCreatesViewHelperInstanceUsingObjectManager()
    {
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->setMethods(array('get'))
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager->expects($this->once())->method('get')->with('x')->willReturn('y');
        $resolver = $this->getMockBuilder(ViewHelperResolver::class)
            ->setMethods(array('getObjectManager'))
            ->getMock();
        $resolver->expects($this->once())->method('getObjectManager')->willReturn($objectManager);
        $this->assertEquals('y', $resolver->createViewHelperInstanceFromClassName('x'));
    }

    /**
     * @test
     * @dataProvider getResolveViewHelperNameTestValues
     * @param string $namespace
     * @param string $method
     * @param string $expected
     */
    public function resolveViewHelperClassNameResolvesExpectedViewHelperClassName($namespace, $method, $expected)
    {
        $viewHelperResolver = new ViewHelperResolver();
        $this->assertEquals($expected, $viewHelperResolver->resolveViewHelperClassName($namespace, $method));
    }

    /**
     * @return array
     */
    public function getResolveViewHelperNameTestValues()
    {
        return array(
            array('f', 'cObject', CObjectViewHelper::class),
            array('f', 'format.htmlentities', HtmlentitiesViewHelper::class),
            array('f', 'render', RenderViewHelper::class)
        );
    }
}
