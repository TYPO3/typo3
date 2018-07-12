<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Database;

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

use TYPO3\CMS\Core\Database\SoftReferenceIndex;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SoftReferenceIndexTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function getTypoLinkPartsThrowExceptionWithPharReferencesDataProvider(): array
    {
        return [
            'URL encoded local' => [
                'phar%3a//some-file.jpg',
            ],
            'URL encoded absolute' => [
                'phar%3a///path/some-file.jpg',
            ],
            'not URL encoded local' => [
                'phar://some-file.jpg',
            ],
            'not URL encoded absolute' => [
                'phar:///path/some-file.jpg',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getTypoLinkPartsThrowExceptionWithPharReferencesDataProvider
     */
    public function getTypoLinkPartsThrowExceptionWithPharReferences(string $pharUrl)
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1530030672);
        (new SoftReferenceIndex())->getTypoLinkParts($pharUrl);
    }
}
