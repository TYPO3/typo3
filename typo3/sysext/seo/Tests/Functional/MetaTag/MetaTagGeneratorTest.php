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

namespace TYPO3\CMS\Seo\Tests\Functional\MetaTag;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Configuration\Image\GraphicsMagickPreset;
use TYPO3\CMS\Seo\MetaTag\MetaTagGenerator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MetaTagGeneratorTest extends FunctionalTestCase
{
    private MetaTagGenerator $subject;
    private ResourceStorage $defaultStorage;
    private vfsStreamDirectory $temporaryFileSystem;
    protected $coreExtensionsToLoad = ['seo'];

    protected function setUp(): void
    {
        parent::setUp();
        // functional tests use GraphicMagick per default, resolve the corresponding path in current OS
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = $this->determineGraphicMagickBinaryPath();
        $this->subject = GeneralUtility::makeInstance(MetaTagGenerator::class);
        $this->defaultStorage = GeneralUtility::makeInstance(StorageRepository::class)->getDefaultStorage();
        $this->temporaryFileSystem = vfsStream::setup();
    }

    public static function socialImageIsProcessedDataProvider(): \Generator
    {
        // having a valid `crop` definition, images are only process if there's a necessity
        yield 'social: 600x600 enforced ratio' => [
            true,
            ['width' => 600, 'height' => 600],
            ['width' => 600, 'height' => 315],
            ProcessedFile::class,
        ];
        yield 'social: 600x315 kept as is' => [
            true,
            ['width' => 600, 'height' => 315],
            ['width' => 600, 'height' => 315],
            File::class,
        ];
        yield 'social: 1200x630 kept as is' => [
            true,
            ['width' => 1200, 'height' => 630],
            ['width' => 1200, 'height' => 630],
            File::class,
        ];
        yield 'social: 2400x1260 limited to maxWidth, kept ratio' => [
            true,
            ['width' => 2400, 'height' => 1260],
            ['width' => 2000, 'height' => 1050],
            ProcessedFile::class,
        ];
        yield 'social: 3000x3000 limited to maxWidth, enforced ratio' => [
            true,
            ['width' => 3000, 'height' => 3000],
            ['width' => 2000, 'height' => 1050],
            ProcessedFile::class,
        ];
        yield 'social: 600x300 enforced ratio (no up-scaling)' => [
            true,
            ['width' => 600, 'height' => 3000],
            ['width' => 600, 'height' => 315],
            ProcessedFile::class,
        ];
        yield 'social: 3000x600 enforced ratio (no up-scaling)' => [
            true,
            ['width' => 3000, 'height' => 600],
            ['width' => 1142, 'height' => 600],
            ProcessedFile::class,
        ];

        // in case `crop` is not defined, no target ratio is defined for these images
        // (data created prior to https://review.typo3.org/c/Packages/TYPO3.CMS/+/58774/ in v9.5.1 behaves like this)
        yield 'empty crop: 600x600 kept as is' => [
            false,
            ['width' => 600, 'height' => 600],
            ['width' => 600, 'height' => 600],
            File::class,
        ];
        yield 'empty crop: 600x315 kept as is' => [
            false,
            ['width' => 600, 'height' => 315],
            ['width' => 600, 'height' => 315],
            File::class,
        ];
        yield 'empty crop: 1200x630 kept as is' => [
            false,
            ['width' => 1200, 'height' => 630],
            ['width' => 1200, 'height' => 630],
            File::class,
        ];
        yield 'empty crop: 2400x1260 limited to maxWidth' => [
            false,
            ['width' => 2400, 'height' => 1260],
            ['width' => 2000, 'height' => 1050],
            ProcessedFile::class,
        ];
        yield 'empty crop: 3000x3000 limited to maxWidth' => [
            false,
            ['width' => 3000, 'height' => 3000],
            ['width' => 2000, 'height' => 2000],
            ProcessedFile::class,
        ];
        yield 'empty crop: 600x300 kept as is' => [
            false,
            ['width' => 600, 'height' => 3000],
            ['width' => 600, 'height' => 3000],
            File::class,
        ];
        yield 'empty crop: 3000x600 limited to maxWidth' => [
            false,
            ['width' => 3000, 'height' => 600],
            ['width' => 2000, 'height' => 400],
            ProcessedFile::class,
        ];
    }

    /**
     * @param array{width: int, height: int} $imageDimension
     * @param array{width: int, height: int} $expectedDimension
     * @test
     * @dataProvider socialImageIsProcessedDataProvider
     */
    public function socialImageIsProcessed(bool $hasCrop, array $imageDimension, array $expectedDimension, string $expectedClassName): void
    {
        $fileName = sprintf('test_%dx%d.png', $imageDimension['width'], $imageDimension['height']);
        $folder = $this->defaultStorage->getFolder('/');
        // drop file if it exists
        $file = $folder->getFile($fileName);
        if ($file !== null) {
            $file->delete();
        }
        // create new file, fill it dummy PNG data for given dimension
        /** @var File $file */
        $file = $this->defaultStorage->createFile($fileName, $folder);
        $file->setContents($this->createImagePngContent($imageDimension['width'], $imageDimension['height']));
        // temporary file reference to an actual existing file
        $fileReferenceProperties = [
            'uid_local' => $file->getUid(),
            'uid_foreign' => 0,
            'uid' => 0,
            'crop' => '',
        ];
        if ($hasCrop) {
            $cropVariantCollection = CropVariantCollection::create('', $this->resolveCropVariantsConfiguration());
            $cropVariantCollection = $cropVariantCollection->applyRatioRestrictionToSelectedCropArea($file);
            $fileReferenceProperties['crop'] = (string)$cropVariantCollection;
        }
        $fileReference = GeneralUtility::makeInstance(ResourceFactory::class)
            ->createFileReferenceObject($fileReferenceProperties);
        // invoke processing of social image
        $reflectionSubject = new \ReflectionObject($this->subject);
        $reflectionMethod = $reflectionSubject->getMethod('processSocialImage');
        $reflectionMethod->setAccessible(true); // no-op in PHP 8.1
        /** @var FileInterface $processedSocialImage */
        $processedSocialImage = $reflectionMethod->invoke($this->subject, $fileReference);

        self::assertSame($expectedDimension, [
            'width' => (int)$processedSocialImage->getProperty('width'),
            'height' => (int)$processedSocialImage->getProperty('height'),
        ]);
        self::assertInstanceOf($expectedClassName, $processedSocialImage);
    }

    private function createImagePngContent(int $width, int $height): string
    {
        $filePath = $this->temporaryFileSystem->url() . '/image.png';
        $gdImage = imagecreatetruecolor($width, $height);
        imagepng($gdImage, $filePath);
        return file_get_contents($filePath);
    }

    private function determineGraphicMagickBinaryPath(): string
    {
        $values = GeneralUtility::makeInstance(GraphicsMagickPreset::class)->getConfigurationValues();
        return $values['GFX/processor_path'] ?? $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'];
    }

    /**
     * A little bit like `\TYPO3\CMS\Backend\Form\Element\ImageManipulationElement::populateConfiguration`...
     */
    private function resolveCropVariantsConfiguration(): array
    {
        $config = $GLOBALS['TCA']['pages']['columns']['og_image']['config']['overrideChildTca']['columns']['crop']['config'];
        $cropVariants = [];
        foreach ($config['cropVariants'] as $id => $cropVariant) {
            // Filter allowed aspect ratios
            $cropVariant['allowedAspectRatios'] = array_filter(
                $cropVariant['allowedAspectRatios'] ?? [],
                static fn ($aspectRatio) => empty($aspectRatio['disabled'])
            );
            // Ignore disabled crop variants
            if (!empty($cropVariant['disabled'])) {
                continue;
            }
            // Enforce a crop area (default is full image)
            if (empty($cropVariant['cropArea'])) {
                $cropVariant['cropArea'] = Area::createEmpty()->asArray();
            }
            $cropVariants[$id] = $cropVariant;
        }
        return $cropVariants;
    }
}
