<?php
namespace TYPO3\CMS\Form\Tests\Unit\PostProcess;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Model\Element;
use TYPO3\CMS\Form\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Form\PostProcess\PostProcessor;
use TYPO3\CMS\Form\Tests\Unit\Fixtures\PostProcessorWithFormPrefixFixture;
use TYPO3\CMS\Form\Tests\Unit\Fixtures\PostProcessorWithoutFormPrefixFixture;
use TYPO3\CMS\Form\Tests\Unit\Fixtures\PostProcessorWithoutInterfaceFixture;

/**
 * Testcase for PostProcessor
 */
class PostProcessorTest extends UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var Element|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $elementProphecy;

    /**
     * @var ObjectManager|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $objectManagerProphecy;

    /**
     * @var ControllerContext|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $controllerContextProphecy;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->elementProphecy = $this->prophesize(Element::class);
        $this->objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $this->controllerContextProphecy = $this->prophesize(ControllerContext::class);
    }

    /**
     * Tears down this test case.
     */
    protected function tearDown()
    {
        parent::tearDown();
        unset($this->elementProphecy);
        unset($this->objectManagerProphecy);
        unset($this->controllerContextProphecy);
    }

    /**
     * @test
     */
    public function processFindsClassSpecifiedByTypoScriptWithoutFormPrefix()
    {
        $typoScript = [
            10 => $this->getUniqueId('postprocess'),
            20 => PostProcessorWithoutFormPrefixFixture::class
        ];

        $this->objectManagerProphecy
            ->get(Argument::cetera())
            ->will(function ($arguments) {
                return new $arguments[0]($arguments[1], $arguments[2]);
            });

        $subject = $this->createSubject($typoScript);
        $this->assertEquals('processedWithoutPrefix', $subject->process());
    }

    /**
     * @test
     */
    public function processFindsClassSpecifiedByTypoScriptWithFormPrefix()
    {
        $typoScript = [
            10 => $this->getUniqueId('postprocess'),
            20 => PostProcessorWithFormPrefixFixture::class
        ];

        $this->objectManagerProphecy
            ->get(Argument::cetera())
            ->will(function ($arguments) {
                return new $arguments[0]($arguments[1], $arguments[2]);
            });

        $subject = $this->createSubject($typoScript);
        $this->assertEquals('processedWithPrefix', $subject->process());
    }

    /**
     * @test
     */
    public function processReturnsEmptyStringIfSpecifiedPostProcessorDoesNotImplementTheInterface()
    {
        $typoScript = [
            10 => $this->getUniqueId('postprocess'),
            20 => PostProcessorWithoutInterfaceFixture::class
        ];

        $this->objectManagerProphecy
            ->get(Argument::cetera())
            ->will(function ($arguments) {
                return new $arguments[0]($arguments[1], $arguments[2]);
            });

        $subject = $this->createSubject($typoScript);
        $this->assertEquals('', $subject->process());
    }

    /**
     * @param array $typoScript
     * @return PostProcessor|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected function createSubject(array $typoScript)
    {
        $subject = $this->getAccessibleMock(
            PostProcessor::class,
            ['__none'],
            [$this->elementProphecy->reveal(), $typoScript]
        );
        $subject->_set('controllerContext', $this->controllerContextProphecy->reveal());
        $subject->_set('objectManager', $this->objectManagerProphecy->reveal());
        return $subject;
    }
}
