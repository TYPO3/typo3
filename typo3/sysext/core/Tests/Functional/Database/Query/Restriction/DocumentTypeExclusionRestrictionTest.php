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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Query\Restriction;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\Restriction\DocumentTypeExclusionRestriction;

final class DocumentTypeExclusionRestrictionTest extends AbstractRestrictionTestCase
{
    public static function buildRestrictionsAddsDoktypeWhereClauseDataProvider(): array
    {
        return [
            'build with one parameter' => [
                [1],
                '"pages"."doktype" NOT IN (1)',
            ],
            'build with multiple parameter' => [
                [1, 4, 100],
                '"pages"."doktype" NOT IN (1, 4, 100)',
            ],
            'build with int parameter' => [
                1,
                '"pages"."doktype" NOT IN (1)',
            ],
        ];
    }

    #[DataProvider('buildRestrictionsAddsDoktypeWhereClauseDataProvider')]
    #[Test]
    public function buildRestrictionsAddsDoktypeWhereClause($excludedDocumentTypes, string $expected): void
    {
        $subject = new DocumentTypeExclusionRestriction($excludedDocumentTypes);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertSame($expected, (string)$expression);
    }
}
