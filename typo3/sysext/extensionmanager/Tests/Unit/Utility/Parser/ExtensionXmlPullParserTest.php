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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility\Parser;

use TYPO3\CMS\Extensionmanager\Utility\Parser\ExtensionXmlPullParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ExtensionXmlPullParserTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider isValidVersionNumberDataProvider
     */
    public function isValidVersionNumber(string $versionNumber, bool $isValid): void
    {
        $subject = $this->getAccessibleMock(ExtensionXmlPullParser::class, ['dummy']);
        $subject->_set('version', $versionNumber);

        self::assertEquals($isValid, $subject->isValidVersionNumber());
    }

    public function isValidVersionNumberDataProvider(): \Generator
    {
        yield 'Successive zeros are not allowed' => [
            '00.2.3',
            false,
        ];
        yield 'Version premodifiers are not allowed' => [
            'v11.2.3',
            false,
        ];
        yield 'Version postmodifiers are not allowed' => [
            '11.2.3-pre-release',
            false,
        ];
        yield 'Characters are not allowed in general' => [
            '11.a.3',
            false,
        ];
        yield 'More than three characters are not allowed' => [
            '11.2.3999',
            false,
        ];
        yield 'Version most use three segements (major, minor, patch)' => [
            '11.2',
            false,
        ];
        yield 'Successive separators are not allowed' => [
            '11..2',
            false,
        ];
        yield 'Leading separator is not allowed' => [
            '.11.2',
            false,
        ];
        yield 'Invalid separator' => [
            '11-2-3',
            false,
        ];
        yield 'Missing separator' => [
            '1123',
            false,
        ];
        yield 'Valid version number' => [
            '11.2.3',
            true,
        ];
    }
}
