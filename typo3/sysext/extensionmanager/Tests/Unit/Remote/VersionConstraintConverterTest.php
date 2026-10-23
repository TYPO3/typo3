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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Remote;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Remote\UnsupportedVersionConstraintException;
use TYPO3\CMS\Extensionmanager\Remote\VersionConstraintConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class VersionConstraintConverterTest extends UnitTestCase
{
    public static function supportedConstraintProvider(): \Generator
    {
        yield 'any version' => ['*', ''];
        yield 'no constraint' => ['', ''];
        yield 'inclusive range' => ['>=13.4.29 <=14.3.99', '13.4.29-14.3.99'];
        yield 'inclusive range without blanks' => ['>=13.4.29<=14.3.99', '13.4.29-14.3.99'];
        yield 'blank after the operator' => ['>= 13.4.29 <= 14.3.99', '13.4.29-14.3.99'];
        yield 'comma separated' => ['>=13.4.29, <=14.3.99', '13.4.29-14.3.99'];
        yield 'exclusive upper bound' => ['>=13.4.0 <15.0.0', '13.4.0-14.999.999'];
        yield 'exclusive lower bound' => ['>13.4.0 <=14.3.99', '13.4.1-14.3.99'];
        yield 'exclusive bound below a minor version' => ['>=1.0.0 <1.1.0', '1.0.0-1.0.999'];
        yield 'short version numbers' => ['>=13.4 <15', '13.4.0-14.999.999'];
        yield 'lower bound only' => ['>=8.2.0', '8.2.0'];
        yield 'exact version' => ['=1.2.3', '1.2.3-1.2.3'];
        yield 'ext_emconf.php range' => ['13.4.0-14.3.99', '13.4.0-14.3.99'];
        yield 'ext_emconf.php minimum' => ['13.4.0', '13.4.0'];
    }

    #[Test]
    #[DataProvider('supportedConstraintProvider')]
    public function aConstraintIsConvertedToTheExtEmconfNotation(string $constraint, string $expected): void
    {
        self::assertSame($expected, VersionConstraintConverter::toEmConfNotation($constraint));
    }

    public static function unsupportedConstraintProvider(): \Generator
    {
        yield 'caret' => ['^13.4'];
        yield 'tilde' => ['~8.2'];
        yield 'alternatives' => ['>=12.4.0 <=12.4.99 || >=13.4.0 <=13.4.99'];
        yield 'not equal' => ['!=1.0.0'];
        yield 'wildcard version' => ['13.4.*'];
        yield 'two lower bounds' => ['>=1.0.0 >=2.0.0'];
    }

    /**
     * Passed on, these would be read as a bogus lowest version by Dependency.
     */
    #[Test]
    #[DataProvider('unsupportedConstraintProvider')]
    public function aConstraintThatCannotBeExpressedIsRejected(string $constraint): void
    {
        $this->expectException(UnsupportedVersionConstraintException::class);
        $this->expectExceptionCode(1794248547);

        VersionConstraintConverter::toEmConfNotation($constraint);
    }
}
