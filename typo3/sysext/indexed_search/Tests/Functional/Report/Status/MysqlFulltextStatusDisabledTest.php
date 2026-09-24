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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\Report\Status;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\IndexedSearch\Report\Status\MysqlFulltextStatus;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MysqlFulltextStatusDisabledTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['indexed_search', 'reports'];

    #[Test]
    public function statusIsEmptyWithoutMysqlFulltext(): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        self::assertSame([], $this->get(MysqlFulltextStatus::class)->getStatus());
    }
}
