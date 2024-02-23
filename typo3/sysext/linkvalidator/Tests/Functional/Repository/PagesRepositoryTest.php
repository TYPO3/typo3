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

namespace TYPO3\CMS\Linkvalidator\Tests\Functional\Repository;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PagesRepositoryTest extends FunctionalTestCase
{
    #[Test]
    public function doesRootLineContainHiddenPagesReturnsCorrectResultWith(): void
    {
        $pageInfo = [
            'uid' => 4,
            'pid' => 3,
            'hidden' => false,
            'extendToSubpages' => false,
        ];

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PagesRepositoryTest/rootline_with_hidden_and_extendToSubpages.csv');
        $pagesRepository = new PagesRepository();
        $result = $pagesRepository->doesRootLineContainHiddenPages($pageInfo);
        self::assertTrue($result);
    }

    #[Test]
    public function doesRootLineContainHiddenPagesReturnsCorrectResultWithout(): void
    {
        $pageInfo = [
            'uid' => 4,
            'pid' => 3,
            'hidden' => false,
            'extendToSubpages' => false,
        ];

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PagesRepositoryTest/rootline_without_hidden_and_extendToSubpages.csv');
        $pagesRepository = new PagesRepository();
        $result = $pagesRepository->doesRootLineContainHiddenPages($pageInfo);
        self::assertFalse($result);
    }
}
