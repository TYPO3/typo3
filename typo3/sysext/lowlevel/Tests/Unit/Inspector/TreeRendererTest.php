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

namespace TYPO3\CMS\Lowlevel\Tests\Unit\Inspector;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Lowlevel\Inspector\ResolvedLabel;
use TYPO3\CMS\Lowlevel\Inspector\StyledText;
use TYPO3\CMS\Lowlevel\Inspector\TreeRenderer;
use TYPO3\CMS\Lowlevel\Inspector\ValueFormatter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TreeRendererTest extends UnitTestCase
{
    private const string HINT_WAYS = '. Add segments, pass --depth=0, or use --flat.';

    /**
     * @return list<string>
     */
    private function render(mixed $data, int $width = 120, ?int $depth = null): array
    {
        return array_map(
            static fn(StyledText $line): string => $line->plain(),
            new TreeRenderer(new ValueFormatter())->render($data, $width, $depth)
        );
    }

    /**
     * @return array<string, int>
     */
    private function scalars(int $count, string $prefix = 'k'): array
    {
        $scalars = [];
        for ($i = 0; $i < $count; $i++) {
            $scalars[$prefix . $i] = $i;
        }
        return $scalars;
    }

    #[Test]
    public function smallDataIsExpandedCompletelyWithChains(): void
    {
        self::assertSame(
            [
                'ctrl',
                '  title = "Content"',
                '  label = "header"',
                'columns header',
                '  label = "Header"',
                '  config',
                '    type = "input"',
                '    max = 255',
                'a b c = 1',
            ],
            $this->render([
                'ctrl' => ['title' => 'Content', 'label' => 'header'],
                'columns' => ['header' => ['label' => 'Header', 'config' => ['type' => 'input', 'max' => 255]]],
                'a' => ['b' => ['c' => 1]],
            ])
        );
    }

    #[Test]
    public function selectedSingleValueIsPrintedCompletely(): void
    {
        $value = str_repeat('z', 300);
        self::assertSame(['"' . $value . '"'], $this->render($value));
    }

    #[Test]
    public function firstLevelIsCompleteEvenBeyondTheBudget(): void
    {
        $lines = $this->render([...$this->scalars(249), 'x' => ['a' => 1]]);
        self::assertCount(250, $lines);
        self::assertSame('k248 = 248', $lines[248]);
        self::assertSame('x a = 1', $lines[249]);
    }

    #[Test]
    public function firstLevelBeyondTheBudgetLeavesBranchesCollapsed(): void
    {
        $lines = $this->render([...$this->scalars(249), 'x' => ['a' => 1, 'b' => 2]]);
        self::assertCount(250, $lines);
        self::assertSame('x = {"a":1,"b":2}', end($lines));
    }

    #[Test]
    public function branchIsInlinedCompletelyWhenTheBudgetIsUsedUp(): void
    {
        $lines = $this->render([...$this->scalars(199), 'x' => ['a' => 1, 'b' => 2, 'c' => 3]]);
        self::assertCount(TreeRenderer::BUDGET, $lines);
        self::assertSame('x = {"a":1,"b":2,"c":3}', end($lines));
    }

    #[Test]
    public function branchTooLongForTheLineBecomesAPreview(): void
    {
        $long = array_fill(0, 3, str_repeat('x', 60));
        $lines = $this->render([...$this->scalars(199), 'x' => $long]);
        $preview = $lines[TreeRenderer::BUDGET - 1];
        self::assertStringStartsWith('x = ["xxx', $preview);
        self::assertStringEndsWith('…', $preview);
        self::assertSame(120, mb_strwidth($preview));
        self::assertSame('→ 1 branch collapsed' . self::HINT_WAYS, end($lines));
    }

    #[Test]
    public function secondPassFoldsSmallFlatBranchToExpandAPreview(): void
    {
        $longChild = array_fill(0, 10, str_repeat('x', 50));
        $lines = $this->render([
            ...$this->scalars(190),
            'small' => ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5],
            'big' => ['c0' => $longChild, 'c1' => $longChild, 'c2' => $longChild, 'c3' => $longChild],
        ]);
        self::assertContains('small = {"a":1,"b":2,"c":3,"d":4,"e":5}', $lines);
        self::assertContains('big', $lines);
        self::assertStringStartsWith('  c0 = ["xxx', $lines[array_search('big', $lines, true) + 1]);
        self::assertSame('→ 4 branches collapsed' . self::HINT_WAYS, end($lines));
        self::assertLessThanOrEqual(TreeRenderer::BUDGET + 1, count($lines));
    }

    #[Test]
    public function longValueIsShortenedToTheWidth(): void
    {
        $lines = $this->render(['a' => str_repeat('y', 300), 'b' => 1]);
        self::assertSame(120, mb_strwidth($lines[0]));
        self::assertStringEndsWith('…', $lines[0]);
        self::assertSame('→ 1 value shortened' . self::HINT_WAYS, end($lines));
    }

    #[Test]
    public function fixedDepthIgnoresTheBudget(): void
    {
        $data = [...$this->scalars(250), 'x' => ['a' => ['b' => 1, 'c' => 2], 'd' => 3]];
        $depthOne = $this->render($data, 120, 1);
        self::assertCount(251, $depthOne);
        self::assertSame('x = {"a":{"b":1,"c":2},"d":3}', end($depthOne));
        self::assertSame(['x', '  a', '    b = 1', '    c = 2', '  d = 3'], array_slice($this->render($data, 120, 0), 250));
    }

    #[Test]
    public function resolvedLabelsCarryTheirReferenceOrACount(): void
    {
        $header = new ResolvedLabel('Header', 'LLL:EXT:a/b.xlf:header');
        self::assertSame(['label = "Header"  # LLL:EXT:a/b.xlf:header'], $this->render(['label' => $header]));
        self::assertSame(
            ['items = ["Header","Header"]  # 2 labels resolved'],
            $this->render(['items' => [$header, $header]], 120, 1)
        );
    }
}
