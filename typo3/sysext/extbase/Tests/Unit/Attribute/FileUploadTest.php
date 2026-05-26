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

namespace TYPO3\CMS\Extbase\Tests\Unit\Attribute;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Extbase\Attribute\FileUpload;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileUploadTest extends UnitTestCase
{
    #[Test]
    public function constructorAcceptsAttributeArguments(): void
    {
        $actual = new FileUpload(
            validation: ['required' => true],
            uploadFolder: '1:/user_upload',
            addRandomSuffix: false,
            createUploadFolderIfNotExist: false,
            duplicationBehavior: DuplicationBehavior::RENAME,
        );

        self::assertSame(['required' => true], $actual->validation);
        self::assertSame('1:/user_upload', $actual->uploadFolder);
        self::assertFalse($actual->addRandomSuffix);
        self::assertFalse($actual->createUploadFolderIfNotExist);
        self::assertSame(DuplicationBehavior::RENAME, $actual->duplicationBehavior);
    }
}
