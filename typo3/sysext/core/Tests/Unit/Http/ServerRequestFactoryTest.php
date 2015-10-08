<?php
namespace TYPO3\CMS\Core\Tests\Unit\Http;

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

use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for \TYPO3\CMS\Core\Http\ServerRequestFactory
 */
class ServerRequestFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Set up
     */
    protected function setUp()
    {
        GeneralUtility::flushInternalRuntimeCaches();
    }

    /**
     * @test
     */
    public function uploadedFilesAreNormalizedFromFilesSuperGlobal()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_FILES = array(
            'tx_uploadexample_piexample' => array(
                'name' => array(
                    'newExample' => array(
                        'image' => 'o51pb.jpg',
                        'imageCollection' => array(
                            0 => 'composer.json',
                        ),
                    ),
                    ),
                    'type' => array(
                        'newExample' => array(
                            'image' => 'image/jpeg',
                            'imageCollection' => array(
                                0 => 'application/json'
                            )
                        )
                    ),
                    'tmp_name' => array(
                        'newExample' => array(
                            'image' => '/Applications/MAMP/tmp/php/phphXdbcd',
                            'imageCollection' => array(
                                0 => '/Applications/MAMP/tmp/php/phpgrZ4bb'
                            )
                        )
                    ),
                    'error' => array(
                        'newExample' => array(
                                'image' => 0,
                                'imageCollection' => array(
                                    0 => 0
                                )
                        )
                    ),
                    'size' => array(
                        'newExample' => array(
                            'image' => 59065,
                            'imageCollection' => array(
                                0 => 683
                            )
                        )
                    )
            )
        );

        $uploadedFiles = ServerRequestFactory::fromGlobals()->getUploadedFiles();

        $this->assertNotEmpty($uploadedFiles['tx_uploadexample_piexample']['newExample']['image']);
        $this->assertTrue($uploadedFiles['tx_uploadexample_piexample']['newExample']['image'] instanceof UploadedFile);
        $this->assertNotEmpty($uploadedFiles['tx_uploadexample_piexample']['newExample']['imageCollection'][0]);
        $this->assertTrue($uploadedFiles['tx_uploadexample_piexample']['newExample']['imageCollection'][0] instanceof UploadedFile);
    }

    /**
     * @test
     */
    public function uploadedFilesAreNotCreatedForEmptyFilesArray()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_FILES = array();

        $uploadedFiles = ServerRequestFactory::fromGlobals()->getUploadedFiles();

        $this->assertEmpty($uploadedFiles);
    }

    /**
     * @test
     */
    public function uploadedFilesAreNotCreatedIfTmpNameIsEmpty()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_FILES = array(
            'tx_uploadexample_piexample' => array(
                'name' => '',
                'tmp_name' => '',
                'error' => 4,
                'size' => 0,
            ),
        );

        $uploadedFiles = ServerRequestFactory::fromGlobals()->getUploadedFiles();

        $this->assertEmpty($uploadedFiles);
    }
}
