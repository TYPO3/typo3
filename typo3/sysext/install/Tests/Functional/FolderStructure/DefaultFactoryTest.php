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

namespace TYPO3\CMS\Install\Tests\Functional\FolderStructure;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DefaultFactoryTest extends FunctionalTestCase
{
    #[Test]
    public function fixCreatesProtectedImportExportFolderButNoTemporaryFolderInFileadmin(): void
    {
        new DefaultFactory()->getStructure()->fix();

        $fileadminPath = Environment::getPublicPath() . '/fileadmin';
        self::assertDirectoryDoesNotExist($fileadminPath . '/_temp_');
        self::assertFileExists($fileadminPath . '/user_upload/_temp_/importexport/.htaccess');
    }
}
