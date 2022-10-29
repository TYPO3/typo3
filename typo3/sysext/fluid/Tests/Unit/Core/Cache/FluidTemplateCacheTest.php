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

namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Cache;

use TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface;
use TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FluidTemplateCacheTest extends UnitTestCase
{
    /**
     * @test
     */
    public function flushCallsFlushOnBackend(): void
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects(self::once())->method('flush');
        $instance = new FluidTemplateCache('dummy', $backend);
        $instance->flush();
    }

    /**
     * @test
     */
    public function getDelegatesToRequireOnce(): void
    {
        $instance = $this->getMockBuilder(FluidTemplateCache::class)
            ->onlyMethods(['requireOnce'])
            ->disableOriginalConstructor()
            ->getMock();
        $instance->expects(self::once())->method('requireOnce')->with('foobar');
        $instance->get('foobar');
    }

    /**
     * @test
     */
    public function setCallsSetOnBackend(): void
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects(self::once())->method('set')->with(
            'test',
            '<?php' . LF . 'test' . LF . '#',
            ['foobar'],
            self::anything()
        );
        $instance = new FluidTemplateCache('dummy', $backend);
        $instance->set('test', 'test', ['foobar']);
    }

    /**
     * @test
     */
    public function setRemovesLeadingPhpTagBeforeCallingParentWhichAddsLeadingPhpTag(): void
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects(self::once())->method('set')->with(
            'test',
            '<?php' . LF . 'test' . LF . '#',
            ['foobar'],
            self::anything()
        );
        $instance = new FluidTemplateCache('dummy', $backend);
        $instance->set('test', '<?php' . LF . 'test', ['foobar']);
    }
}
