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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\TextExtraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Resource\TextExtraction\PlainTextExtractor;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PlainTextExtractorTest extends FunctionalTestCase
{
    #[Test]
    public function extractTextKeepsOriginalFileOfLocalStorageIfItsNameDiffersFromTheIdentifier(): void
    {
        $path = Environment::getPublicPath() . '/fileadmin/user_upload/data.txt';
        GeneralUtility::mkdir_deep(dirname($path));
        file_put_contents($path, 'quokka');
        $file = new File(
            ['uid' => 1, 'identifier' => '/user_upload/data.txt', 'name' => 'Data.txt', 'mime_type' => 'text/plain'],
            $this->get(StorageRepository::class)->findByUid(1)
        );

        $text = new PlainTextExtractor()->extractText($file);

        self::assertSame('quokka', $text);
        self::assertFileExists($path);
    }
}
