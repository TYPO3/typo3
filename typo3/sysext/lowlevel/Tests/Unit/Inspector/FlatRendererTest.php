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
use TYPO3\CMS\Lowlevel\Inspector\FlatRenderer;
use TYPO3\CMS\Lowlevel\Inspector\Marker;
use TYPO3\CMS\Lowlevel\Inspector\ResolvedLabel;
use TYPO3\CMS\Lowlevel\Inspector\StyledText;
use TYPO3\CMS\Lowlevel\Inspector\ValueFormatter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FlatRendererTest extends UnitTestCase
{
    /**
     * @return list<string>
     */
    private function render(mixed $data): array
    {
        return array_map(
            static fn(StyledText $line): string => $line->plain(),
            new FlatRenderer(new ValueFormatter())->render($data)
        );
    }

    #[Test]
    public function writesOneLinePerValueWithQuotedPath(): void
    {
        self::assertSame(
            [
                'frontend typo3/cms-frontend/timetracker target = "Timetracker"',
                "backend enforce \"package 'typo3/cms-backend'\" = 1",
                'empty = []',
                'nothing = null',
                'ratio = INF',
                'label = "Header"',
            ],
            $this->render([
                'frontend' => ['typo3/cms-frontend/timetracker' => ['target' => 'Timetracker']],
                'backend' => ['enforce' => ["package 'typo3/cms-backend'" => 1]],
                'empty' => [],
                'nothing' => null,
                'ratio' => new Marker('INF'),
                'label' => new ResolvedLabel('Header', 'LLL:EXT:a/b.xlf:header'),
            ])
        );
    }

    #[Test]
    public function writesSelectedSingleValueOnly(): void
    {
        self::assertSame(['"New TYPO3 site"'], $this->render('New TYPO3 site'));
    }
}
