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

namespace TYPO3\CMS\Backend\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ThumbnailViewHelperTest extends FunctionalTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/backend/Tests/Functional/ViewHelpers/Fixtures/ThumbnailViewHelper/Folders/fileadmin/' => 'fileadmin/',
    ];

    #[Test]
    public function sysFileAsImageAttrbuteReturnsExpectedImageTag(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ThumbnailViewHelper/fal_image.csv');
        $resourceFactory = $this->get(ResourceFactory::class);
        $file = $resourceFactory->getFileObject(1);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:thumbnail image="{imageObject}" />');
        $context->getVariableProvider()->add('imageObject', $file);
        $expected = '<img src="fileadmin/_processed_/3/7/preview_ImageViewHelperFalTest_252565634e.jpg" width="64" height="48" alt="alt text from metadata" />';

        self::assertEquals($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function sysFileReferenceAsImageAttributeReturnsExpectedImageTag(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ThumbnailViewHelper/fal_image.csv');
        $resourceFactory = $this->get(ResourceFactory::class);
        $file = $resourceFactory->getFileReferenceObject(1);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:thumbnail image="{imageObject}" />');
        $context->getVariableProvider()->add('imageObject', $file);
        $expected = '<img src="fileadmin/_processed_/3/7/preview_ImageViewHelperFalTest_252565634e.jpg" width="64" height="48" alt="alt text from metadata" />';

        self::assertEquals($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function missingSysFileReferenceLookupThrowsException(): void
    {
        $this->expectException(ResourceDoesNotExistException::class);
        $this->expectExceptionCode(1317178794);

        $this->importCSVDataSet(__DIR__ . '/Fixtures/ThumbnailViewHelper/fal_image.csv');
        $resourceFactory = $this->get(ResourceFactory::class);
        $file = $resourceFactory->getFileReferenceObject(42);
    }

    #[Test]
    public function focusAreaAttributeIsRelativeToProcessedImage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ThumbnailViewHelper/fal_image.csv');
        $resourceFactory = $this->get(ResourceFactory::class);
        $file = $resourceFactory->getFileObject(1);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<be:thumbnail image="{imageObject}" crop="{crop}" />');
        $context->getVariableProvider()->add('imageObject', $file);
        $context->getVariableProvider()->add('crop', '{"default":{"cropArea":{"x":0.25,"y":0.25,"width":0.5,"height":0.5},"selectedRatio":"NaN","focusArea":{"x":0.5,"y":0.5,"width":0.25,"height":0.5}}}');

        // Source is 400x300, cropped to 200x150 and previewed at 64x48. The focus
        // area is stored relative to the crop area, so it must be based on the
        // processed image.
        self::assertMatchesRegularExpression(
            '@^<img data-focus-area="\{&quot;x&quot;:32,&quot;y&quot;:24,&quot;width&quot;:16,&quot;height&quot;:24\}" src="fileadmin/_processed_/.*\.jpg" width="64" height="48".*/>$@',
            (new TemplateView($context))->render(),
        );
    }
}
