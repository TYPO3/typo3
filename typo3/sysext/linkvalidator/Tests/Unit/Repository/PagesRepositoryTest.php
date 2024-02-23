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

namespace TYPO3\CMS\Linkvalidator\Tests\Unit\Repository;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PagesRepositoryTest extends UnitTestCase
{
    #[Test]
    public function doesRootLineContainHiddenPagesReturnTrueForCurrentPage(): void
    {
        $subject = new PagesRepository();
        $pageInfo = [
            'uid' => 1,
            'pid' => 0,
            'hidden' => 1,
            'extendToSubpages' => 0,
        ];
        $result = $subject->doesRootLineContainHiddenPages($pageInfo);

        self::assertTrue($result);
    }
}
