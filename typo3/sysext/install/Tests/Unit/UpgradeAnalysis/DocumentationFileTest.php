<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Tests\Unit\UpgradeAnalysis;

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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Install\UpgradeAnalysis\DocumentationFile;

class DocumentationFileTest extends UnitTestCase
{

    /**
     * @var DocumentationFile
     */
    protected $documentationFileService;

    /**
     * @var  vfsStreamDirectory
     */
    protected $docRoot;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $content_12345 = [
            '====',
            'Breaking: #12345 - Issue',
            '====',
            '',
            'some text content',
        ];
        $content_45678 = [
            '====',
            'Important: #45678 - Issue',
            '====',
            '',
            'Some more text content',
        ];

        $content_98574 = [
            '====',
            'Important: #98574 - Issue',
            '====',
            '',
            'Something else',
            '',
            '.. index:: unittest'
        ];
        $content_13579 = [
            '====',
            'Breaking: #13579 - Issue',
            '====',
            '',
            'Some more content'
        ];

        $structure = [
            'Changelog' => [
                '1.2' => [
                    'Breaking-12345-Issue.rst' => implode("\n", $content_12345),
                    'Important-45678-Issue.rst' => implode("\n", $content_45678),

                ],
                '2.0' => [
                    'Important-98574-Issue.rst' => implode("\n", $content_98574),
                ],
                'master' => [
                    'Breaking-13579-Issue.rst' => implode("\n", $content_13579),
                    'Index.rst' => '',
                ],
            ],
        ];

        $this->docRoot = vfsStream::setup('root', null, $structure);
        $this->documentationFileService = new DocumentationFile();
    }

    /**
     * @test
     */
    public function findDocumentationFilesReturnsArrayOfFiles()
    {
        $expected = [
            '1.2' => [],
            '2.0' => [],
            'master' => [],
        ];

        $result = $this->documentationFileService->findDocumentationFiles(vfsStream::url('root/Changelog'));
        self::assertEquals(array_keys($expected), array_keys($result));
    }

    /**
     * @test
     */
    public function extractingTagsProvidesTagsAsDesired()
    {
        $expected = [
            'unittest',
            'cat:Important',
        ];
        $result = $this->documentationFileService->findDocumentationFiles(vfsStream::url('root/Changelog'));
        self::assertEquals($expected, $result['2.0'][98574]['tags']);
    }
}
