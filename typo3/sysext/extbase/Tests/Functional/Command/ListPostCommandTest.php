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

namespace TYPO3\CMS\Extbase\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\Command\AbstractCommandTestCase;

final class ListPostCommandTest extends AbstractCommandTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/CountTestImport.csv');
    }

    #[Test]
    public function correctCountIsReturned(): void
    {
        $result = $this->executeConsoleCommand('blogxample:listpost');
        self::assertStringContainsString('[OK] Found 14 posts', $result['stdout']);
    }
}
