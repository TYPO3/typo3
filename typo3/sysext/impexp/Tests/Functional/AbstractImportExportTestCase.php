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

namespace TYPO3\CMS\Impexp\Tests\Functional;

use PHPUnit\Util\Xml\Loader as XmlLoader;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Abstract used by ext:impexp functional tests
 */
abstract class AbstractImportExportTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['impexp', 'form'];

    protected array $testFilesToDelete = [];

    /**
     * Set up for set up the backend user, initialize the language object
     * and creating the Export instance
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseImports/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 0;
        Bootstrap::initializeLanguageObject();

        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('route', new Route('/record/importexport/export', []))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', new NormalizedParams([], [], '', ''));
    }

    /**
     * Tear down for remove of the test files
     */
    protected function tearDown(): void
    {
        foreach ($this->testFilesToDelete as $absoluteFileName) {
            if (@is_file($absoluteFileName)) {
                unlink($absoluteFileName);
            }
        }
        parent::tearDown();
    }

    /**
     * Test if the local filesystem is case sensitive.
     * Needed for some export related tests
     */
    protected function isCaseSensitiveFilesystem(): bool
    {
        $caseSensitive = true;
        $path = GeneralUtility::tempnam('aAbB');

        // do the actual sensitivity check
        if (@file_exists(strtoupper($path)) && @file_exists(strtolower($path))) {
            $caseSensitive = false;
        }

        // clean filesystem
        unlink($path);
        return $caseSensitive;
    }

    /**
     * Asserts that two XML documents are equal.
     */
    public function assertXmlStringEqualsXmlFileWithIgnoredSqliteTypeInteger(string $expectedFile, string $actualXml): void
    {
        $actual = (new XmlLoader())->load($actualXml);
        $expectedFileContent = file_get_contents($expectedFile);
        $expected = (new XmlLoader())->load($expectedFileContent);
        self::assertEquals($expected, $actual);
    }
}
