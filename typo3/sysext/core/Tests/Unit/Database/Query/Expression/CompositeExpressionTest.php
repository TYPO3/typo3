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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Query\Expression;

use Doctrine\DBAL\Query\Expression\CompositeExpression as DoctrineCompositeExpression;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CompositeExpressionTest extends UnitTestCase
{
    #[Test]
    public function orCompositeExpressionWithMethodFiltersNullParts(): void
    {
        $compositeExpression = (new CompositeExpression(CompositeExpression::TYPE_OR))->with(null, null);
        self::assertCount(0, $compositeExpression);
    }

    #[Test]
    public function andCompositeExpressionWithMethodFiltersNullParts(): void
    {
        $compositeExpression = (new CompositeExpression(CompositeExpression::TYPE_OR))->with(null, null);
        self::assertCount(0, $compositeExpression);
    }

    #[Test]
    public function orCompositeExpressionWithMethodFiltersEmptyStringParts(): void
    {
        $compositeExpression = (new CompositeExpression(CompositeExpression::TYPE_OR))->with('', '');
        self::assertCount(0, $compositeExpression);
    }

    #[Test]
    public function constructorEmptyPartsAreFilteredOutForOrType(): void
    {
        self::assertCount(0, new CompositeExpression(CompositeExpression::TYPE_OR, ['', null, '( ( ) )']));
    }

    #[Test]
    public function constructorEmptyPartsAreFilteredOutForAndType(): void
    {
        self::assertCount(0, new CompositeExpression(CompositeExpression::TYPE_OR, ['', null, '( ( ) )']));
    }

    #[Test]
    public function andCompositeExpressionWithMethodFiltersEmptyStringParts(): void
    {
        $compositeExpression = (new CompositeExpression(CompositeExpression::TYPE_OR))->with('', '');
        self::assertCount(0, $compositeExpression);
    }

    public static function isEmptyPartTrimCharFromStringValueDataSets(): \Generator
    {
        yield 'start brace' => [
            'value' => '(',
        ];
        yield 'end brace' => [
            'value' => '(',
        ];
        yield 'space' => [
            'value' => ' ',
        ];
        yield 'spaces' => [
            'value' => '   ',
        ];
        yield 'start and end brace with spaces' => [
            'value' => ' ( (()) )',
        ];
    }

    #[DataProvider('isEmptyPartTrimCharFromStringValueDataSets')]
    #[Test]
    public function orCompositeExpressionWithMethodFiltersEmptyStringAfterTrimChars(string $value): void
    {
        $compositeExpression = (new CompositeExpression(CompositeExpression::TYPE_OR))->with($value, $value);
        self::assertCount(0, $compositeExpression);
    }

    #[DataProvider('isEmptyPartTrimCharFromStringValueDataSets')]
    #[Test]
    public function andCompositeExpressionWithMethodFiltersEmptyStringAfterTrimChars(string $value): void
    {
        $compositeExpression = (new CompositeExpression(CompositeExpression::TYPE_OR))->with($value, $value);
        self::assertCount(0, $compositeExpression);
    }

    #[Test]
    public function staticIsEmptyPartReturnsTrueForNull(): void
    {
        self::assertTrue($this->callStaticIsEmptyPartMethod(null));
    }

    #[Test]
    public function staticIsEmptyPartReturnsTrueForEmptyString(): void
    {
        self::assertTrue($this->callStaticIsEmptyPartMethod(''));
    }

    #[Test]
    public function staticIsEmptyDoesNotCallToStringWhenHavingNoPartsAndReturnsTrue(): void
    {
        $compositeExpressionMock = $this->createMock(CompositeExpression::class);
        $compositeExpressionMock->__construct(CompositeExpression::TYPE_AND);
        $compositeExpressionMock
            ->expects(self::never())
            ->method('__toString')
            ->willReturn('');
        self::assertTrue($this->callStaticIsEmptyPartMethod($compositeExpressionMock));
    }

    #[Test]
    public function staticIsEmptyDoesNotCallToStringWhenHavingNoPartsBecauseOfFilteredPartsAndReturnsTrue(): void
    {
        $compositeExpressionMock = $this->createMock(CompositeExpression::class);
        $compositeExpressionMock->__construct(CompositeExpression::TYPE_AND, [null, '', '( ( ) )']);
        $compositeExpressionMock
            ->expects(self::never())
            ->method('__toString')
            ->willReturn('');
        self::assertTrue($this->callStaticIsEmptyPartMethod($compositeExpressionMock));
    }

    private function callStaticIsEmptyPartMethod(CompositeExpression|DoctrineCompositeExpression|string|null $value): bool
    {
        return (new \ReflectionClass(CompositeExpression::class))
          ->getMethod('isEmptyPart')
          ->invoke(null, $value);
    }
}
