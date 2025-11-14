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

namespace TYPO3\CMS\Core\Tests\Unit\Text;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Text\TextCropper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TextCropperTest extends UnitTestCase
{
    #[Test]
    public function cropIsMultibyteSafe(): void
    {
        self::assertEquals('бла', (new TextCropper())->crop('бла', 3, '...', false));
    }

    public static function cropWorksDataProvider(): \Generator
    {
        $plainText = 'Kasper Sk' . chr(229) . 'rh' . chr(248)
            . 'j implemented the original version of the crop function.';
        $textWithLinebreaks = "Lorem ipsum dolor sit amet,\n"
            . "consetetur sadipscing elitr,\n"
            . 'sed diam nonumy eirmod tempor invidunt ut labore e'
            . 't dolore magna aliquyam';

        yield 'plain text; 11|...|0' => [
            'expected' => 'Kasper Sk' . chr(229) . 'r...',
            'content' => $plainText,
            'numberOfChars' => 11,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'plain text; -58|...|0' => [
            'expected' => '...h' . chr(248) . 'j implemented the original version of the crop function.',
            'content' => $plainText,
            'numberOfChars' => -58,
            'replacementForEllipsis' => '...',
            'cropToSpace' => false,
        ];

        yield 'plain text; 4|...|1' => [
            'expected' => 'Kasp...',
            'content' => $plainText,
            'numberOfChars' => 4,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'plain text; 20|...|1' => [
            'expected' => 'Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j...',
            'content' => $plainText,
            'numberOfChars' => 20,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'plain text; -5|...|1' => [
            'expected' => '...tion.',
            'content' => $plainText,
            'numberOfChars' => -5,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        yield 'plain text; -49|...|1' => [
            'expected' => '... the original version of the crop function.',
            'content' => $plainText,
            'numberOfChars' => -49,
            'replacementForEllipsis' => '...',
            'cropToSpace' => true,
        ];

        // text with linebreaks
        yield 'text with linebreaks' => [
            'expected' => "Lorem ipsum dolor sit amet,\nconsetetur sadipscing elitr,\ns"
                . 'ed diam nonumy eirmod tempor invidunt ut labore e'
                . 't dolore magna',
            'content' => $textWithLinebreaks,
            'numberOfChars' => 121,
            'replacementForEllipsis' => '',
            'cropToSpace' => false,
        ];
    }

    #[DataProvider('cropWorksDataProvider')]
    #[Test]
    public function cropWorks(string $expected, string $content, int $numberOfChars, string $replacementForEllipsis, bool $cropToSpace): void
    {
        $content = mb_convert_encoding($content, 'utf-8', 'iso-8859-1');
        $expected = mb_convert_encoding($expected, 'utf-8', 'iso-8859-1');
        self::assertEquals($expected, (new TextCropper())->crop($content, $numberOfChars, $replacementForEllipsis, $cropToSpace));
    }
}
