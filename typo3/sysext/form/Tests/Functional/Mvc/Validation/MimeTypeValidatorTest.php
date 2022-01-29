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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Validation;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\PseudoFile;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MimeTypeValidatorTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    /**
     * @var array<string, string>
     */
    private $files = [
        'file.exe' => "MZ\x90\x00\x03\x00",
        'file.zip' => "PK\x03\x04",
        'file.jpg' => "\xFF\xD8\xFF\xDB",
        'file.gif' => 'GIF87a',
        'file.pdf' => '%PDF-',
    ];

    /**
     * @var vfsStreamDirectory
     */
    private $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        $this->tmp = vfsStream::setup('tmp', null, $this->files);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['LANG'], $this->tmp);
        parent::tearDown();
    }

    public function dataProvider(): array
    {
        // error-codes
        // + 1471708998: mime-type not allowed
        // + 1613126216: mime-type to file-extension mismatch
        return [
            'submitted gif as upload.gif' => [
                [
                    'tmp_name' => 'vfs://tmp/file.gif',
                    'name' => 'upload.gif',
                    'type' => 'does/not-matter',
                ],
                ['image/gif'],
            ],
            'submitted jpg as upload.jpg' => [
                [
                    'tmp_name' => 'vfs://tmp/file.jpg',
                    'name' => 'upload.jpg',
                    'type' => 'does/not-matter',
                ],
                ['image/jpeg'],
            ],
            'submitted pdf as upload.pdf' => [
                [
                    'tmp_name' => 'vfs://tmp/file.pdf',
                    'name' => 'upload.pdf',
                    'type' => 'does/not-matter',
                ],
                ['application/pdf'],
            ],
            'submitted exe as upload.exe' => [
                [
                    'tmp_name' => 'vfs://tmp/file.exe',
                    'name' => 'upload.exe',
                    'type' => 'does/not-matter',
                ], // upload data (as in $_FILES)
                ['image/gif'], // allowed mime-types
                [1471708998], // expected error-codes
            ],
            'submitted gif as upload.exe' => [
                [
                    'tmp_name' => 'vfs://tmp/file.gif',
                    'name' => 'upload.exe',
                    'type' => 'does/not-matter',
                ], // upload data (as in $_FILES)
                ['image/gif'], // allowed mime-types
                [1613126216], // expected error-codes
            ],
        ];
    }

    /**
     * @param array<string, int|string> $uploadData
     * @param List<string> $allowedMimeTypes
     * @param List<int> $expectedErrorCodes
     *
     * @test
     * @dataProvider dataProvider
     */
    public function someTest(array $uploadData, array $allowedMimeTypes, array $expectedErrorCodes = []): void
    {
        $uploadData['error'] = \UPLOAD_ERR_OK;
        $uploadData['size'] = filesize($uploadData['tmp_name']);

        $validator = new MimeTypeValidator(['allowedMimeTypes' => $allowedMimeTypes]);

        $resource = new PseudoFile($uploadData);
        $result = $validator->validate($resource);
        $errorCodes = array_map([$this, 'resolveErrorCode'], $result->getErrors());
        self::assertSame($expectedErrorCodes, $errorCodes);
    }

    private function resolveErrorCode(Error $error): int
    {
        return $error->getCode();
    }
}
