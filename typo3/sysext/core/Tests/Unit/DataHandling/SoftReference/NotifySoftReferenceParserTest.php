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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling\SoftReference;

class NotifySoftReferenceParserTest extends AbstractSoftReferenceParserTest
{
    /**
     * @test
     */
    public function notifySoftReferenceParserTest(): void
    {
        $subject = $this->getParserByKey('notify');
        $result = $subject->parse('aTable', 'aField', 1, 'fooBar');
        $expectedElements = [
            [
                'matchString' => 'fooBar',
            ],
        ];
        self::assertSame($expectedElements, $result->getMatchedElements());
    }
}
