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

namespace TYPO3\CMS\Core\Tests\Functional\LinkHandling;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\LinkHandling\FolderLinkHandler;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FolderLinkHandlerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function resolveReturnsNullFolderForRemovedFallbackStorage(): void
    {
        $subject = new FolderLinkHandler();
        self::assertSame(['folder' => null], $subject->resolveHandlerData(['storage' => '0', 'identifier' => '/typo3conf/']));
    }
}
