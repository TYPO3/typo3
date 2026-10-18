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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Property\TypeConverter;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\PseudoFileReference;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PseudoFileReferenceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin/user_upload/');
    }

    protected function tearDown(): void
    {
        $uploadPath = Environment::getPublicPath() . '/fileadmin/user_upload/';
        if (is_dir($uploadPath)) {
            GeneralUtility::rmdir($uploadPath, true);
        }
        parent::tearDown();
    }

    #[Test]
    public function serializationOfTransientReferenceOnlyExposesTheFileUid(): void
    {
        $subject = $this->createUploadedFileReference();
        $fileUid = $subject->getOriginalResource()->getOriginalFile()->getUid();

        self::assertSame(
            'O:' . strlen(PseudoFileReference::class) . ':"' . PseudoFileReference::class . '":1:{s:9:"_uidLocal";i:' . $fileUid . ';}',
            serialize($subject)
        );
    }

    #[Test]
    public function unserializationOfTransientReferenceRestoresTheOriginalResource(): void
    {
        $subject = $this->createUploadedFileReference();
        $fileUid = $subject->getOriginalResource()->getOriginalFile()->getUid();

        $restored = unserialize(serialize($subject), ['allowed_classes' => [PseudoFileReference::class]]);

        self::assertInstanceOf(PseudoFileReference::class, $restored);
        self::assertSame($fileUid, $restored->getOriginalResource()->getOriginalFile()->getUid());
        self::assertSame('test.pdf', $restored->getOriginalResource()->getOriginalFile()->getName());
    }

    #[Test]
    public function unserializationWithoutUidThrows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1613216548);
        unserialize('O:' . strlen(PseudoFileReference::class) . ':"' . PseudoFileReference::class . '":0:{}', ['allowed_classes' => [PseudoFileReference::class]]);
    }

    private function createUploadedFileReference(): PseudoFileReference
    {
        $temporaryPath = $this->instancePath . '/typo3temp/var/transient/';
        GeneralUtility::mkdir_deep($temporaryPath);
        copy(__DIR__ . '/Fixtures/test-document.pdf', $temporaryPath . 'test.pdf');

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(
            UploadedFileReferenceConverter::class,
            [
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED => 'test-seed',
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ]
        );
        $result = $this->get(UploadedFileReferenceConverter::class)->convertFrom(
            [
                'error' => \UPLOAD_ERR_OK,
                'name' => 'test.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $temporaryPath . 'test.pdf',
                'size' => filesize($temporaryPath . 'test.pdf'),
            ],
            FileReference::class,
            [],
            $configuration
        );
        self::assertInstanceOf(PseudoFileReference::class, $result);
        return $result;
    }
}
