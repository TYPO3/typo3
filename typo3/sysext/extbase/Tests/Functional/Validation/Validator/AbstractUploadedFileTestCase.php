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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation\Validator;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractUploadedFileTestCase extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * Tear down for remove of the test files
     */
    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/tmp', true);
        parent::tearDown();
    }

    /**
     * Helper function to create a test file with the given content.
     */
    protected function createTestFile(string $filename, string $content): string
    {
        $path = $this->instancePath . '/tmp';
        $testFilename = $path . $filename;

        mkdir($path);
        touch($testFilename);
        file_put_contents($testFilename, $content);

        return $testFilename;
    }
}
