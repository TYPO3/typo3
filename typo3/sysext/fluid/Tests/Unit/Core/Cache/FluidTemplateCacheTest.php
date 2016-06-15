<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Cache;

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
use TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache;

/**
 * Test case
 */
class FluidTemplateCacheTest extends UnitTestCase
{
    /**
     * @test
     */
    public function flushCallsFlushOnBackend()
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects($this->once())->method('flush');
        $instance = new FluidTemplateCache('dummy', $backend);
        $instance->flush();
    }

    /**
     * @test
     */
    public function getDelegatesToRequireOnce()
    {
        $instance = $this->getMockBuilder(FluidTemplateCache::class)
            ->setMethods(array('requireOnce'))
            ->disableOriginalConstructor()
            ->getMock();
        $instance->expects($this->once())->method('requireOnce')->with('foobar');
        $instance->get('foobar');
    }

    /**
     * @test
     */
    public function setCallsSetOnBackend()
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects($this->once())->method('set')->with(
            'test',
            '<?php' . LF . 'test' . LF . '#',
            array('foobar'),
            $this->anything()
        );
        $instance = new FluidTemplateCache('dummy', $backend);
        $instance->set('test', 'test', array('foobar'));
    }

    /**
     * @test
     */
    public function setRemovesLeadingPhpTagBeforeCallingParentWhichAddsLeadingPhpTag()
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects($this->once())->method('set')->with(
            'test',
            '<?php' . LF . 'test' . LF . '#',
            array('foobar'),
            $this->anything()
        );
        $instance = new FluidTemplateCache('dummy', $backend);
        $instance->set('test', '<?php' . LF . 'test', array('foobar'));
    }
}
