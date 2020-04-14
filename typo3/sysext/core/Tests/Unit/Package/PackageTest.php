<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Package;

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackagePathException;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the package class
 */
class PackageTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup('Packages');
    }

    /**
     * @test
     */
    public function constructThrowsPackageDoesNotExistException()
    {
        $this->expectException(InvalidPackagePathException::class);
        $this->expectExceptionCode(1166631890);

        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->expects(self::any())->method('isPackageKeyValid')->willReturn(true);
        new Package($packageManagerMock, 'Vendor.TestPackage', './ThisPackageSurelyDoesNotExist');
    }

    public function validPackageKeys()
    {
        return [
            ['Doctrine.DBAL'],
            ['TYPO3.CMS'],
            ['My.Own.TwitterPackage'],
            ['GoFor.IT'],
            ['Symfony.Symfony']
        ];
    }

    /**
     * @test
     * @dataProvider validPackageKeys
     */
    public function constructAcceptsValidPackageKeys($packageKey)
    {
        $packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
        file_put_contents($packagePath . 'ext_emconf.php', '');

        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->expects(self::any())->method('isPackageKeyValid')->willReturn(true);
        $package = new Package($packageManagerMock, $packageKey, $packagePath);
        self::assertEquals($packageKey, $package->getPackageKey());
    }

    public function invalidPackageKeys()
    {
        return [
            ['TYPO3..Flow'],
            ['RobertLemke.Flow. Twitter'],
            ['Schalke*4']
        ];
    }

    /**
     * @test
     * @dataProvider invalidPackageKeys
     */
    public function constructRejectsInvalidPackageKeys($packageKey)
    {
        $this->expectException(InvalidPackageKeyException::class);
        $this->expectExceptionCode(1217959511);

        $packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
        mkdir($packagePath, 0777, true);

        $packageManagerMock = $this->createMock(PackageManager::class);
        new Package($packageManagerMock, $packageKey, $packagePath);
    }

    /**
     * @test
     */
    public function aPackageCanBeFlaggedAsProtected()
    {
        $packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
        mkdir($packagePath, 0700, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');
        file_put_contents($packagePath . 'ext_emconf.php', '');

        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->expects(self::any())->method('isPackageKeyValid')->willReturn(true);
        $package = new Package($packageManagerMock, 'Vendor.Dummy', $packagePath);

        self::assertFalse($package->isProtected());
        $package->setProtected(true);
        self::assertTrue($package->isProtected());
    }
}
