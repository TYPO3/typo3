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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GraphicalFunctionsTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function imageMagickIdentifyReturnsFormattedValues(): void
    {
        $file = 'myImageFile.png';
        $expected = [
            '123',
            '234',
            'png',
            'myImageFile.png',
            'png',
        ];

        $subject = $this->getAccessibleMock(GraphicalFunctions::class, ['executeIdentifyCommandForImageFile'], [], '', false);
        $subject->_set('processorEnabled', true);
        $subject->expects($this->once())->method('executeIdentifyCommandForImageFile')->with($file)->willReturn('123 234 png PNG');
        $result = $subject->imageMagickIdentify($file);
        self::assertEquals($result, $expected);
    }

    #[Test]
    public function imageMagickIdentifyReturnsFormattedValuesWithOffset(): void
    {
        $file = 'myImageFile.png';
        $expected = [
            '200+0+0',
            '400+0+0',
            'png',
            'myImageFile.png',
            'png',
        ];

        $subject = $this->getAccessibleMock(GraphicalFunctions::class, ['executeIdentifyCommandForImageFile'], [], '', false);
        $subject->_set('processorEnabled', true);
        $subject->expects($this->once())->method('executeIdentifyCommandForImageFile')->with($file)->willReturn('200+0+0 400+0+0 png PNG');
        $result = $subject->imageMagickIdentify($file);
        self::assertEquals($result, $expected);
    }
}
