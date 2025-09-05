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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface;
use TYPO3\CMS\Fluid\Core\Cache\FluidTemplateCache;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FluidTemplateCacheTest extends UnitTestCase
{
    #[Test]
    public function flushCallsFlushOnBackend(): void
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects($this->once())->method('flush');
        $instance = new FluidTemplateCache('dummy', $backend);
        $instance->flush();
    }

    #[Test]
    public function getDelegatesToRequireOnce(): void
    {
        $instance = $this->getMockBuilder(FluidTemplateCache::class)
            ->onlyMethods(['requireOnce'])
            ->disableOriginalConstructor()
            ->getMock();
        $instance->expects($this->once())->method('requireOnce')->with('foobar');
        $instance->get('foobar');
    }

    #[Test]
    public function setCallsSetOnBackend(): void
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects($this->once())->method('set')->with(
            'test',
            '<?php' . LF . 'test' . LF . '#',
            ['foobar'],
            self::anything()
        );
        $instance = new FluidTemplateCache('dummy', $backend);
        $instance->set('test', 'test', ['foobar']);
    }

    #[Test]
    public function setRemovesLeadingPhpTagBeforeCallingParentWhichAddsLeadingPhpTag(): void
    {
        $backend = $this->createMock(PhpCapableBackendInterface::class);
        $backend->expects($this->once())->method('set')->with(
            'test',
            '<?php' . LF . 'test' . LF . '#',
            ['foobar'],
            self::anything()
        );
        $instance = new FluidTemplateCache('dummy', $backend);
        $instance->set('test', '<?php' . LF . 'test', ['foobar']);
    }
}
