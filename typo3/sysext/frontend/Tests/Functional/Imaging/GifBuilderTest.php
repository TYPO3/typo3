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

namespace TYPO3\CMS\Frontend\Tests\Functional\Imaging;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Testcase for TYPO3\CMS\Frontend\Imaging\GifBuilder
 */
class GifBuilderTest extends FunctionalTestCase
{
    /**
     * Check hashes of Images overlayed with other images are idempotent
     *
     * @test
     */
    public function overlayImagesHasStableHash()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);

        copy(
            Environment::getFrameworkBasePath() . '/frontend/Tests/Functional/Fixtures/Images/kasper-skarhoj1.jpg',
            Environment::getPublicPath() . '/fileadmin/kasper-skarhoj1.jpg'
        );

        $storageRepository = (new StorageRepository())->findByUid(1);
        $file = $storageRepository->getFile('kasper-skarhoj1.jpg');

        self::assertFalse($file->isMissing());

        $fileArray = [
            'XY' => '[10.w],[10.h]',
            'format' => 'jpg',
            'quality' => 88,
            '10' => 'IMAGE',
            '10.' => [
                'file.width' => 300,
                'file' => $file,
            ],
            '30' => 'IMAGE',
            '30.' => [
                'file' => $file,
                'file.' => [
                    'align' => 'l,t',
                    'width' => 100
                ]
            ]
        ];

        $gifBuilder = new GifBuilder();
        $gifBuilder->start($fileArray, []);
        $setup1 = $gifBuilder->setup;
        $fileName1 = $gifBuilder->gifBuild();

        // Recreate a fresh GifBuilder instance, to catch inconsistencies in hashing for different instances
        $gifBuilder = new GifBuilder();
        $gifBuilder->start($fileArray, []);
        $setup2 = $gifBuilder->setup;
        $fileName2 = $gifBuilder->gifBuild();

        self::assertSame($setup1, $setup2, 'The Setup resulting from two equal configurations must be equal');
        self::assertSame($fileName1, $fileName2);
    }
}
