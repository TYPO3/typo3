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

namespace TYPO3\CMS\FrontendLogin\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserGroupRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FrontendUserGroupRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['felogin'];

    #[Test]
    public function findRedirectPageIdByGroupId(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/fe_groups.csv');
        $subject = new FrontendUserGroupRepository(new ConnectionPool());
        self::assertNull($subject->findRedirectPageIdByGroupId(99));
        self::assertSame(10, $subject->findRedirectPageIdByGroupId(1));
    }
}
