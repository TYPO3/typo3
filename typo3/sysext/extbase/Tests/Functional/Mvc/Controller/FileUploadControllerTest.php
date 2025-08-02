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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\ExtbaseUpload\Domain\Model\Singlefile;
use TYPO3Tests\ExtbaseUpload\Domain\Repository\SinglefileRepository;

final class FileUploadControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/extbase_upload'];
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            // Force some encryptionKey so the data provider hashes are "stable" even if TF setup changes encryptionKey.
            'encryptionKey' => '1234123412341234123412341234123412341234123412341234123412341234',
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixture/FileUploadData.csv');
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:extbase/Tests/Functional/Fixtures/Extensions/extbase_upload/Configuration/TypoScript/setup.typoscript',
                'EXT:extbase/Tests/Functional/Fixtures/Extensions/extbase_upload/Configuration/TypoScript/Frontend/setup.typoscript',
                'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
            ]
        );
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ]
        );
    }

    #[Test]
    public function createANewRecordThroughExtbaseShowsProperForm(): void
    {
        $args = [
            'id' => 3,
            'tx_extbaseupload_pi1[action]' => 'new',
            'tx_extbaseupload_pi1[controller]' => 'SingleFileUpload',
        ];
        $args['cHash'] = $this->get(CacheHashCalculator::class)->generateForParameters(HttpUtility::buildQueryString($args));
        $detailLink = '/de/home?' . HttpUtility::buildQueryString($args);
        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com' . $detailLink);
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<input id="persist-title" type="text" name="tx_extbaseupload_pi1[item][title]" />', (string)$response->getBody());
    }

    public static function fileUploadDataProvider(): \Generator
    {
        // @todo: Remove when core raised to minimum PHP 8.4
        $exeMimeType = version_compare(PHP_VERSION, '8.4', '>=') ? 'application/vnd.microsoft.portable-executable' : 'application/x-dosexec';

        /*
         * The "Unrestricted" extbase upload should allow to upload everything except:
         * - no .php files!
         * - only files with matching/detected MIME types (no "exe" masked as "jpg").
         *
         * NOTES: Yes, uploading files not listed in textfile_ext/mediafile_ext/miscfile_ext is ALLOWED
         *       (independent from the SYS.feature.enforceAllowedFileExtensions).
         *       If that is wanted, use the FileExtensionValidator with "useStorageDefaults" option.
         *       Also, yes, the setting SYS.feature.enforceFileExtensionMimeTypeConsistency is ignored, too (always enforced due to security).
         */
        yield 'fileUnrestrictedSingle: EXE' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test.exe',
            // EXE files allowed because "unrestricted" does not utilize the FileExtensionValidator.
            'expectation' => true,
            'actualMimeType' => 'application/x-msdos-program',
            'expectedErrors' => [
                'item.fileUnrestrictedSingle.1754045716: The resolved media type &quot;' . $exeMimeType . '&quot; is not allowed for file extension &quot;exe&quot;.',
            ],
        ];
        yield 'fileUnrestrictedSingle: JPG' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test.jpg',
            'expectation' => true,
            'actualMimeType' => 'image/jpeg',
        ];
        yield 'fileUnrestrictedSingle: PDF' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test.pdf',
            'expectation' => true,
            'actualMimeType' => 'application/pdf',
        ];
        yield 'fileUnrestrictedSingle: PHP' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test.php',
            // .php files always denied.
            'expectation' => false,
            'actualMimeType' => 'text/x-php',
            'expectedErrors' => [
                'item.fileUnrestrictedSingle.1711367029: Uploading of files with PHP executable file extensions is not allowed.',
                'item.fileUnrestrictedSingle.1754045716: The resolved media type &quot;text/x-php&quot; is not allowed for file extension &quot;php&quot;.',
            ],
        ];
        yield 'fileUnrestrictedSingle: TXT' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test.txt',
            'expectation' => true,
            'actualMimeType' => 'text/plain',
        ];
        yield 'fileUnrestrictedSingle: WEBP' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test.webp',
            // WEBP files allowed because "unrestricted" does not utilize the FileExtensionValidator, so mediafile_ext is irrelevant here.
            'expectation' => true,
            'actualMimeType' => 'image/webp',
        ];
        yield 'fileUnrestrictedSingle: EXE masked as TXT' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test-is-exe.txt',
            // Mismatching MIME-Types are always denied.
            'expectation' => false,
            'actualMimeType' => 'application/x-msdos-program',
            'expectedErrors' => [
                'item.fileUnrestrictedSingle.1754045716: The resolved media type &quot;' . $exeMimeType . '&quot; is not allowed for file extension &quot;txt&quot;.',
            ],
        ];
        yield 'fileUnrestrictedSingle: JPG masked as EXE' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test-is-jpg.exe',
            // Mismatching MIME-Types are always denied.
            'expectation' => false,
            'actualMimeType' => 'image/jpeg',
            'expectedErrors' => [
                'item.fileUnrestrictedSingle.1754045716: The resolved media type &quot;image/jpeg&quot; is not allowed for file extension &quot;exe&quot;.',
            ],
        ];
        yield 'fileUnrestrictedSingle: JPG masked as TXT' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test-is-jpg.txt',
            // Mismatching MIME-Types are always denied.
            'expectation' => false,
            'actualMimeType' => 'image/jpeg',
            'expectedErrors' => [
                'item.fileUnrestrictedSingle.1754045716: The resolved media type &quot;image/jpeg&quot; is not allowed for file extension &quot;txt&quot;.',
            ],
        ];
        yield 'fileUnrestrictedSingle: PDF masked as TXT' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test-is-pdf.txt',
            // Mismatching MIME-Types are always denied.
            'expectation' => false,
            'actualMimeType' => 'application/pdf',
            'expectedErrors' => [
                'item.fileUnrestrictedSingle.1754045716: The resolved media type &quot;application/pdf&quot; is not allowed for file extension &quot;txt&quot;.',
            ],
        ];
        yield 'fileUnrestrictedSingle: WEBP masked as JPG' => [
            'property' => 'fileUnrestrictedSingle',
            'filename' => 'test-is-webp.jpg',
            // Mismatching MIME-Types are always denied.
            'expectation' => false,
            'actualMimeType' => 'image/webp',
            'expectedErrors' => [
                'item.fileUnrestrictedSingle.1754045716: The resolved media type &quot;image/webp&quot; is not allowed for file extension &quot;jpg&quot;.',
            ],
        ];

        /**
         * "ImageSingle" uses the MimeTypeValidator set to "image/jpeg".
         */
        yield 'fileImageSingle: JPG' => [
            'property' => 'fileImageSingle',
            'filename' => 'test.jpg',
            'expectation' => true,
            'actualMimeType' => 'image/jpeg',
        ];
        yield 'fileImageSingle: PDF' => [
            'property' => 'fileImageSingle',
            'filename' => 'test.pdf',
            // Only "image/jpeg" is listed
            'expectation' => false,
            'actualMimeType' => 'application/pdf',
            'expectedErrors' => [
                'item.fileImageSingle.1708538973: The mime type &#039;application/pdf&#039; is not allowed.',
            ],
        ];
        yield 'fileImageSingle: JPG masked as text' => [
            'property' => 'fileImageSingle',
            'filename' => 'test-is-jpg.txt',
            // Mismatching MIME-Type
            'expectation' => false,
            'actualMimeType' => 'image/jpeg',
            'expectedErrors' => [
                'item.fileImageSingle.1718469466: The file extension provided &quot;txt&quot; does not match to expected media types.',
                'item.fileImageSingle.1754045716: The resolved media type &quot;image/jpeg&quot; is not allowed for file extension &quot;txt&quot;.',
            ],
        ];
        yield 'fileImageSingle: WEBP masked as jpeg' => [
            'property' => 'fileImageSingle',
            'filename' => 'test-is-webp.jpg',
            // Mismatching MIME-Type
            'expectation' => false,
            'actualMimeType' => 'image/webp',
            'expectedErrors' => [
                'item.fileImageSingle.1708538973: The mime type &#039;image/webp&#039; is not allowed.',
                'item.fileImageSingle.1754045716: The resolved media type &quot;image/webp&quot; is not allowed for file extension &quot;jpg&quot;.',
            ],
        ];

        /**
         * "AppSingle" uses the MimeTypeValidator set to a plethora of EXE types plus PDF.
         */
        yield 'fileAppSingle: EXE' => [
            'property' => 'fileAppSingle',
            'filename' => 'test.exe',
            'expectation' => true,
            'actualMimeType' => 'application/x-msdos-program',
        ];
        yield 'fileAppSingle: PDF' => [
            'property' => 'fileAppSingle',
            'filename' => 'test.pdf',
            'expectation' => true,
            'actualMimeType' => 'application/pdf',
        ];
        yield 'fileAppSingle: JPG' => [
            'property' => 'fileAppSingle',
            'filename' => 'test.jpg',
            'expectation' => false,
            'actualMimeType' => 'image/jpeg',
            'expectedErrors' => [
                'item.fileAppSingle.1708538973: The mime type &#039;image/jpeg&#039; is not allowed.',
            ],
        ];
        // Does not need more expectations, covered by "fileImageSingle".

        /**
         * "ExtensionSingle" uses the FileExtensionValidator to allow ".exe" files
         */
        yield 'fileExtensionSingle: EXE' => [
            'property' => 'fileExtensionSingle',
            'filename' => 'test.exe',
            'expectation' => true,
            'actualMimeType' => 'application/x-msdos-program',
        ];
        yield 'fileExtensionSingle: JPG masked as exe' => [
            'property' => 'fileExtensionSingle',
            'filename' => 'test-is-jpg.exe',
            // Mismatching MIME-Type
            'expectation' => false,
            'actualMimeType' => 'image/jpeg',
            'expectedErrors' => [
                'item.fileExtensionSingle.1754045716: The resolved media type &quot;image/jpeg&quot; is not allowed for file extension &quot;exe&quot;.',
            ],
        ];
        yield 'fileExtensionSingle: JPG' => [
            'property' => 'fileExtensionSingle',
            'filename' => 'test.jpg',
            'expectation' => false,
            'actualMimeType' => 'image/jpeg',
            'expectedErrors' => [
                'item.fileExtensionSingle.1754043401: The file extension &#039;jpg&#039; is not allowed.',
            ],
        ];

        /**
         * "ExtensionstorageSingle" uses the FileExtensionValidator with "useStorageDefaults" to allow everything set in misc+text+mediafile_ext
         */
        yield 'fileExtensionstorageSingle: TXT' => [
            'property' => 'fileExtensionstorageSingle',
            'filename' => 'test.txt',
            // Allowed via "textfile_ext"
            'expectation' => true,
            'actualMimeType' => 'text/plain',
        ];
        yield 'fileExtensionstorageSingle: HTML' => [
            'property' => 'fileExtensionstorageSingle',
            'filename' => 'test.html',
            // Allowed via "textfile_ext"
            'expectation' => true,
            'actualMimeType' => 'text/html',
        ];
        yield 'fileExtensionstorageSingle: XML' => [
            'property' => 'fileExtensionstorageSingle',
            'filename' => 'test.xml',
            // Allowed via "miscfile_ext"
            'expectation' => true,
            'actualMimeType' => 'text/xml',
        ];
        yield 'fileExtensionstorageSingle: PDF' => [
            'property' => 'fileExtensionstorageSingle',
            'filename' => 'test.pdf',
            // Allowed via "mediafile_ext"
            'expectation' => true,
            'actualMimeType' => 'application/pdf',
        ];
        yield 'fileExtensionstorageSingle: EXE' => [
            'property' => 'fileExtensionstorageSingle',
            'filename' => 'test.exe',
            // Not allowed via any media/misc/textfile_ext.
            'expectation' => false,
            'actualMimeType' => $exeMimeType,
            'expectedErrors' => [
                'item.fileExtensionstorageSingle.1754043401: The file extension &#039;exe&#039; is not allowed.',
            ],
        ];

        /**
         * "ExtensionstorageplusSingle" uses the FileExtensionValidator with "useStorageDefaults" to allow everything set in misc+text+mediafile_ext, PLUS also "exe" files.
         */
        yield 'fileExtensionstorageplusSingle: TXT' => [
            'property' => 'fileExtensionstorageplusSingle',
            'filename' => 'test.txt',
            // Allowed via "textfile_ext"
            'expectation' => true,
            'actualMimeType' => 'text/plain',
        ];
        yield 'fileExtensionstorageplusSingle: HTML' => [
            'property' => 'fileExtensionstorageplusSingle',
            'filename' => 'test.html',
            // Allowed via "textfile_ext"
            'expectation' => true,
            'actualMimeType' => 'text/html',
        ];
        yield 'fileExtensionstorageplusSingle: XML' => [
            'property' => 'fileExtensionstorageplusSingle',
            'filename' => 'test.xml',
            // Allowed via "miscfile_ext"
            'expectation' => true,
            'actualMimeType' => 'text/xml',
        ];
        yield 'fileExtensionstorageplusSingle: PDF' => [
            'property' => 'fileExtensionstorageplusSingle',
            'filename' => 'test.pdf',
            // Allowed via "mediafile_ext"
            'expectation' => true,
            'actualMimeType' => 'application/pdf',
        ];
        yield 'fileExtensionstorageplusSingle: EXE' => [
            'property' => 'fileExtensionstorageplusSingle',
            'filename' => 'test.exe',
            // Allowed via custom filename
            'expectation' => true,
            'actualMimeType' => 'application/x-msdos-program',
        ];
        yield 'fileExtensionstorageplusSingle: WEBP' => [
            'property' => 'fileExtensionstorageplusSingle',
            'filename' => 'test.webp',
            // Not allowed via any media/misc/textfile_ext or custom filename
            'expectation' => false,
            'actualMimeType' => 'image/webp',
            'expectedErrors' => [
                'item.fileExtensionstorageplusSingle.1754043401: The file extension &#039;webp&#039; is not allowed.',
            ],
        ];
    }

    #[DataProvider('fileUploadDataProvider')]
    #[Test]
    public function uploadingANewFileThroughExtbaseValidatesOnFileConfiguration(string $property, string $filename, bool $expectation, string $actualMimeType, array $expectedErrors = []): void
    {
        // We expect all variations of $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.system.enforceAllowedFileExtensions']
        // and $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.system.enforceFileExtensionMimeTypeConsistency'] (true+true,true+false,false+true,false+false)
        // to yield the same expectations, because Extbase should have an independent configuration.
        // Also, we do not want Extbase to turn off MIME type resource integrity.
        $this->uploadFileAndCompareWithExpectation($property, $filename, $expectation, $actualMimeType, $expectedErrors, true, true, 1);
        $this->uploadFileAndCompareWithExpectation($property, $filename, $expectation, $actualMimeType, $expectedErrors, true, false, 2);
        $this->uploadFileAndCompareWithExpectation($property, $filename, $expectation, $actualMimeType, $expectedErrors, false, true, 3);
        $this->uploadFileAndCompareWithExpectation($property, $filename, $expectation, $actualMimeType, $expectedErrors, false, false, 4);
    }

    private function uploadFileAndCompareWithExpectation(
        string $property,
        string $filename,
        bool $expectation,
        string $actualMimeType,
        array $expectedErrors,
        bool $enforceAllowedFileExtensions,
        bool $enforceFileExtensionMimeTypeConsistency,
        int $expectedFileUid,
    ): void {
        // Write additional TYPO3_CONF_VARS to this functional test specific system config directory
        // since we change details per data provider tuple.
        $additionalConfVars = '<?php
$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'textfile_ext\'] = \'txt,html\';
$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'mediafile_ext\'] = \'jpg,pdf\';
$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'miscfile_ext\'] = \'zip,xml\';
$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'features\'][\'security.system.enforceAllowedFileExtensions\'] = ' . ($enforceAllowedFileExtensions ? 'true' : 'false') . ';
$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'features\'][\'security.system.enforceFileExtensionMimeTypeConsistency\'] = ' . ($enforceFileExtensionMimeTypeConsistency ? 'true' : 'false') . ';
';
        file_put_contents(Environment::getLegacyConfigPath() . '/system/additional.php', $additionalConfVars);

        $postLink = '/de/home';
        $args = [
            'id' => 3,
            'tx_extbaseupload_pi1' => [
                'action' => 'create',
                'controller' => 'SingleFileUpload',
            ],
        ];
        $args['cHash'] = $this->get(CacheHashCalculator::class)->generateForParameters(HttpUtility::buildQueryString($args));
        $formData = [
            'tx_extbaseupload_pi1' => [
                'item' => [
                    'title' => 'A new file upload',
                ],
                '__referrer' => [
                    '@extension' => 'ExtbaseUpload',
                    '@controller' => 'SingleFileUpload',
                    '@action' => 'new',
                    'arguments' => 'YToyOntzOjY6ImFjdGlvbiI7czozOiJuZXciO3M6MTA6ImNvbnRyb2xsZXIiO3M6MTY6IlNpbmdsZUZpbGVVcGxvYWQiO30=b44e907017c75d6aac95090e84844abb49cb6656',
                    '@request' => '{"@extension":"ExtbaseUpload","@controller":"SingleFileUpload","@action":"new"}307fb3e16a6ec5a77e02274eaf8bbfecd6824707',
                ],
                '__trustedProperties' => '{"item":{"title":1,"fileUnrestrictedSingle":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1},"fileImageSingle":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1},"fileAppSingle":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1},"fileExtensionSingle":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1},"fileExtensionstorageSingle":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1},"fileExtensionstorageplusSingle":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1},"fileUnrestrictedMulti":{"*":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1}},"fileImageMulti":{"*":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1}},"fileAppMulti":{"*":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1}},"fileExtensionMulti":{"*":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1}},"fileExtensionstorageMulti":{"*":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1}},"fileExtensionstorageplusMulti":{"*":{"name":1,"type":1,"tmp_name":1,"error":1,"size":1}}}}185bcffa0b7dd76e82de133a4c6d6eec424260df',
            ],
        ];
        $requestContext = new InternalRequestContext();

        // Create a test file for upload
        $testFilePath = __DIR__ . '/Fixture/Uploads/' . $filename;
        $testFileName = basename($testFilePath);

        // Note: This file gets unlinked after successful upload, so we need a tempfile for it.
        $temporaryFileToUpload = $testFilePath . '.upload';
        copy($testFilePath, $temporaryFileToUpload);

        // Prepare file data
        $fileData = [
            'tx_extbaseupload_pi1' => [
                'item' => [
                    $property => [
                        'name' => $testFileName,
                        'type' => 'application/typo3-test', // client-supplied MIME-Type is not evaluated (see assertion)
                        'tmp_name' => $temporaryFileToUpload,
                        'error' => UPLOAD_ERR_OK,
                        'size' => filesize($temporaryFileToUpload),
                    ],
                ],
            ],
        ];

        // Create multipart body
        $boundary = '----WebKitFormBoundary' . uniqid();
        $multipartBody = $this->createMultipartBody($formData, $fileData, $boundary);
        $bodyStream = $this->createStreamFromString($multipartBody);

        $request = (new InternalRequest('https://www.acme.com' . $postLink))
            ->withMethod('POST')
            ->withQueryParams($args)
            ->withParsedBody($formData)
            ->withUploadedFiles($this->createUploadedFilesArray($fileData)) // Add uploaded files
            ->withBody($bodyStream)
            ->withAddedHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);

        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        if (!$expectation) {
            // Expecting validation errors.
            preg_match_all(
                '@<p class="validationError">(.+?)</p>@ims',
                (string)$response->getBody(),
                $errorMatches
            );
            self::assertSame($errorMatches[1], $expectedErrors);
        }

        $singlefileRepository = $this->get(SinglefileRepository::class);
        /** @var ?Singlefile $singlefile */
        $singlefile = $singlefileRepository->findByUid($expectedFileUid);
        if ($expectation) {
            self::assertSame('A new file upload', $singlefile->getTitle());
            $getMethod = 'get' . ucfirst($property);
            self::assertSame($testFileName, $singlefile->$getMethod()?->getOriginalResource()->getOriginalFile()->getName());
            self::assertSame($actualMimeType, $singlefile->$getMethod()->getOriginalResource()->getOriginalFile()->getMimeType());
        } else {
            self::assertNull($singlefile);
            @unlink($temporaryFileToUpload);
        }
    }

    /**
     * Helpers to create HTTP POST File Upload. Might be easier using a Symfony Message/Multipart dependency?
     */
    private function createMultipartBody(array $formData, array $fileData, string $boundary): string
    {
        $body = '';
        // Add form fields
        foreach ($this->flattenArray($formData) as $name => $value) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
            $body .= "{$value}\r\n";
        }
        // Add file fields
        foreach ($this->flattenFileArray($fileData) as $fieldName => $fileInfo) {
            if (is_file($fileInfo['tmp_name'])) {
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Disposition: form-data; name=\"{$fieldName}\"; filename=\"{$fileInfo['name']}\"\r\n";
                $body .= "Content-Type: {$fileInfo['type']}\r\n\r\n";
                $body .= file_get_contents($fileInfo['tmp_name']) . "\r\n";
            }
        }
        $body .= "--{$boundary}--\r\n";
        return $body;
    }

    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '[' . $key . ']';
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    private function flattenFileArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '[' . $key . ']';
            if (isset($value['name']) && isset($value['tmp_name'])) {
                // This is a file field
                $result[$newKey] = $value;
            } elseif (is_array($value)) {
                $result = array_merge($result, $this->flattenFileArray($value, $newKey));
            }
        }
        return $result;
    }

    private function createUploadedFilesArray(array $fileData): array
    {
        $uploadedFiles = [];
        foreach ($this->flattenFileArray($fileData) as $fieldName => $fileInfo) {
            if (is_file($fileInfo['tmp_name'])) {
                // Create UploadedFile instance - adjust based on your TYPO3 version
                $uploadedFiles = $this->setNestedArrayValue(
                    $uploadedFiles,
                    $fieldName,
                    new UploadedFile(
                        $fileInfo['tmp_name'],
                        $fileInfo['size'],
                        $fileInfo['error'],
                        $fileInfo['name'],
                        $fileInfo['type']
                    )
                );
            }
        }
        return $uploadedFiles;
    }

    private function setNestedArrayValue(array &$array, string $key, $value): array
    {
        // Convert bracket notation to dot notation
        $key = str_replace(['[', ']'], ['.', ''], $key);
        $keys = explode('.', $key);
        $current = &$array;
        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        $current = $value;
        return $array;
    }

    private function createStreamFromString(string $content): StreamInterface
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        return new Stream($stream);
    }
}
