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

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Extbase\Attribute\FileUpload;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileUploadTest extends UnitTestCase
{
    #[Test]
    #[IgnoreDeprecations]
    public function constructorAcceptsConfigurationOptionsAsArray(): void
    {
        $this->expectUserDeprecationMessage(
            'Passing an array of configuration values to Extbase attributes will be removed in TYPO3 v15.0. ' .
            'Use explicit constructor parameters instead.',
        );

        $actual = new FileUpload([
            'validation' => [
                'required' => true,
            ],
            'addRandomSuffix' => false,
            'uploadFolder' => '1:/user_upload',
            'createUploadFolderIfNotExist' => false,
            'duplicationBehavior' => DuplicationBehavior::RENAME,
        ]);

        self::assertSame(['required' => true], $actual->validation);
        self::assertSame('1:/user_upload', $actual->uploadFolder);
        self::assertFalse($actual->addRandomSuffix);
        self::assertFalse($actual->createUploadFolderIfNotExist);
        self::assertSame(DuplicationBehavior::RENAME, $actual->duplicationBehavior);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function constructorThrowsExceptionIfInvalidValueForDuplicationBehaviorIsPassed(): void
    {
        $this->expectExceptionObject(
            new \RuntimeException('Wrong annotation configuration for "duplicationBehavior". Ensure, that the value is a valid DuplicationBehavior.', 1711453150),
        );

        new FileUpload([
            'duplicationBehavior' => 'foo',
        ]);
    }
}
