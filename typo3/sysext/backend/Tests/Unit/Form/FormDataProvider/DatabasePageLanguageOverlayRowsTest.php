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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabasePageLanguageOverlayRowsTest extends UnitTestCase
{
    protected DatabasePageLanguageOverlayRows&MockObject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(DatabasePageLanguageOverlayRows::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDatabaseRows'])
            ->getMock();
    }

    #[Test]
    public function addDataSetsPageLanguageOverlayRows(): void
    {
        $input = [
            'effectivePid' => '23',
        ];
        $expected = $input;
        $expected['pageLanguageOverlayRows'] = [
            0 => [
                'uid' => '1',
                'pid' => '42',
                'sys_language_uid' => '2',
            ],
        ];
        $this->subject->expects($this->once())
            ->method('getDatabaseRows')
            ->willReturn($expected['pageLanguageOverlayRows']);

        self::assertSame($expected, $this->subject->addData($input));
    }
}
