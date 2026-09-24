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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\TextExtraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TextExtractorRegistrationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['indexed_search'];

    protected bool $initializeDatabase = false;

    #[Test]
    public function indexedSearchExtractorsAreRegistered(): void
    {
        $extensions = $this->get(TextExtractorRegistry::class)->getSupportedFileExtensions();
        foreach (['html', 'htm', 'xml', 'csv', 'jpg', 'jpeg', 'tif', 'txt'] as $extension) {
            self::assertContains($extension, $extensions);
        }
    }
}
