<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extensionmanager\Tests\UnitDeprecated\Utility;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class InstallUtilityTest extends UnitTestCase
{
    /**
     * @var array List of created fake extensions to be deleted in tearDown() again
     */
    protected $fakedExtensions = [];

    /**
     */
    protected function tearDown()
    {
        foreach ($this->fakedExtensions as $fakeExtkey => $fakeExtension) {
            $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/' . $fakeExtkey;
        }
        parent::tearDown();
    }

    /**
     * Creates a fake extension inside typo3temp/. No configuration is created,
     * just the folder
     *
     * @return string The extension key
     */
    protected function createFakeExtension(): string
    {
        $extKey = strtolower($this->getUniqueId('testing'));
        $absExtPath = Environment::getVarPath() . '/tests/' . $extKey;
        $relPath = 'typo3temp/var/tests/' . $extKey . '/';
        GeneralUtility::mkdir($absExtPath);
        $this->fakedExtensions[$extKey] = [
            'siteRelPath' => $relPath,
        ];

        return $extKey;
    }

    /**
     * @test
     */
    public function processDatabaseUpdatesCallsUpdateDbWithExtTablesSql()
    {
        $extKey = $this->createFakeExtension();
        $extPath = Environment::getVarPath() . '/tests/' . $extKey . '/';
        $extTablesFile = $extPath . 'ext_tables.sql';
        $fileContent = 'DUMMY TEXT TO COMPARE';
        file_put_contents($extTablesFile, $fileContent);
        $installMock = $this->getAccessibleMock(
            InstallUtility::class,
            ['updateDbWithExtTablesSql', 'importStaticSqlFile', 'importT3DFile'],
            [],
            '',
            false
        );
        $dependencyUtility = $this->getMockBuilder(DependencyUtility::class)->getMock();
        $installMock->_set('dependencyUtility', $dependencyUtility);

        $installMock->expects($this->once())->method('updateDbWithExtTablesSql')->with($this->stringStartsWith($fileContent));
        $installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
    }

    /**
     * @test
     */
    public function processDatabaseUpdatesCallsImportStaticSqlFile()
    {
        $extKey = $this->createFakeExtension();
        $extensionSiteRelPath = 'typo3temp/var/tests/' . $extKey . '/';
        $installMock = $this->getAccessibleMock(
            InstallUtility::class,
            ['importStaticSqlFile', 'updateDbWithExtTablesSql', 'importT3DFile'],
            [],
            '',
            false
        );
        $dependencyUtility = $this->getMockBuilder(DependencyUtility::class)->getMock();
        $installMock->_set('dependencyUtility', $dependencyUtility);
        $installMock->expects($this->once())->method('importStaticSqlFile')->with($extensionSiteRelPath);
        $installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
    }

    /**
     * @return array
     */
    public function processDatabaseUpdatesCallsImportFileDataProvider(): array
    {
        return [
            'T3D file' => [
                'data.t3d',
            ],
            'XML file' => [
                'data.xml',
            ],
        ];
    }

    /**
     * @param string $fileName
     * @test
     * @dataProvider processDatabaseUpdatesCallsImportFileDataProvider
     */
    public function processDatabaseUpdatesCallsImportFile($fileName)
    {
        $extKey = $this->createFakeExtension();
        $absPath = Environment::getPublicPath() . '/' . $this->fakedExtensions[$extKey]['siteRelPath'];
        GeneralUtility::mkdir($absPath . '/Initialisation');
        file_put_contents($absPath . '/Initialisation/' . $fileName, 'DUMMY');
        $installMock = $this->getAccessibleMock(
            InstallUtility::class,
            ['updateDbWithExtTablesSql', 'importStaticSqlFile', 'importT3DFile'],
            [],
            '',
            false
        );
        $dependencyUtility = $this->getMockBuilder(DependencyUtility::class)->getMock();
        $installMock->_set('dependencyUtility', $dependencyUtility);
        $installMock->expects($this->once())->method('importT3DFile')->with($this->fakedExtensions[$extKey]['siteRelPath']);
        $installMock->processDatabaseUpdates($this->fakedExtensions[$extKey]);
    }
}
