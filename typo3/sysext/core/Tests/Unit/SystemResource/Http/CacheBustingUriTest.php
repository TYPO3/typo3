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

namespace TYPO3\CMS\Core\Tests\Unit\SystemResource\Http;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SystemResource\Http\CacheBustingUri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CacheBustingUriTest extends UnitTestCase
{
    private \SplFileInfo $assetFile;

    protected function setUp(): void
    {
        parent::setUp();

        $testFileDirectory = Environment::getVarPath() . '/tests/';
        GeneralUtility::mkdir_deep($testFileDirectory);
        $assetFile = $testFileDirectory . StringUtility::getUniqueId() . '_asset_file.css';
        $this->testFilesToDelete[] = $assetFile;
        touch($assetFile);
        $this->assetFile = new \SplFileInfo($assetFile);
    }

    #[Test]
    public function cacheBustingAddsMtimeAsQueryString(): void
    {
        $uri = CacheBustingUri::fromFileSystemPath(
            $this->assetFile->getPathname(),
            new Uri('/tests/' . $this->assetFile->getBasename()),
            ApplicationType::BACKEND
        );

        self::assertSame((string)$this->assetFile->getMTime(), $uri->getQuery());
    }

    #[Test]
    public function noCacheBustingIsAddedWhenFileDoesNotExist(): void
    {
        $baseUri = new Uri('/tests/' . $this->assetFile->getBasename());
        $uri = CacheBustingUri::fromFileSystemPath(
            $this->assetFile->getPathname() . 'x',
            $baseUri,
            ApplicationType::BACKEND
        );

        self::assertSame((string)$baseUri, (string)$uri);
    }

    #[Test]
    public function cacheBustingIsAddedToGivenBaseUri(): void
    {
        $baseUri = new Uri('https://foo.test/tests/' . StringUtility::getUniqueId() . $this->assetFile->getBasename());
        $uri = CacheBustingUri::fromFileSystemPath(
            $this->assetFile->getPathname(),
            $baseUri,
            ApplicationType::FRONTEND
        );

        self::assertSame((string)$baseUri, (string)$uri->withQuery(''));
        self::assertSame((string)$this->assetFile->getMTime(), $uri->getQuery());
    }

    #[Test]
    public function cacheBustingRespectsExistingQuery(): void
    {
        $baseUri = new Uri('https://foo.test/tests/' . StringUtility::getUniqueId() . $this->assetFile->getBasename() . '?foo=bar');
        $uri = CacheBustingUri::fromFileSystemPath(
            $this->assetFile->getPathname(),
            $baseUri,
            ApplicationType::FRONTEND
        );

        self::assertSame('foo=bar&' . $this->assetFile->getMTime(), $uri->getQuery());
    }

    #[Test]
    public function versionNumberedFilenameIsWorkingInBackend(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['versionNumberInFilename'] = true;

        $uri = CacheBustingUri::fromFileSystemPath(
            $this->assetFile->getPathname(),
            new Uri('/tests/' . $this->assetFile->getBasename()),
            ApplicationType::BACKEND
        );

        self::assertSame('/tests/' . $this->assetFile->getBasename('.css') . '.' . $this->assetFile->getMTime() . '.css', $uri->getPath());
    }

    #[Test]
    public function versionNumberedFilenameIsWorkingInFrontend(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename'] = true;

        $uri = CacheBustingUri::fromFileSystemPath(
            $this->assetFile->getPathname(),
            new Uri('/tests/' . $this->assetFile->getBasename()),
            ApplicationType::FRONTEND
        );

        self::assertSame('/tests/' . $this->assetFile->getBasename('.css') . '.' . $this->assetFile->getMTime() . '.css', $uri->getPath());
    }
}
