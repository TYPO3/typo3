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

namespace TYPO3\CMS\Form\Tests\Functional\ViewHelpers\Form;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class UploadedResourceViewHelperTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected bool $initializeDatabase = false;

    #[Test]
    public function accpetAttributeIsAdded(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<formvh:form.uploadedResource accept="{0: \'image/jpeg\', 1: \'image/png\'}"/>');
        self::assertSame('<input accept="image/jpeg,image/png" type="file" name="" />', (new TemplateView($context))->render());
    }

    #[Test]
    public function multipleAttributeIsNotAddedByDefault(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<formvh:form.uploadedResource />');
        $result = (new TemplateView($context))->render();
        self::assertStringNotContainsString('multiple', $result);
        self::assertSame('<input type="file" name="" />', $result);
    }

    #[Test]
    public function multipleAttributeIsAddedWhenSetToTrue(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<formvh:form.uploadedResource multiple="true" />');
        $result = (new TemplateView($context))->render();
        self::assertStringContainsString('multiple="multiple"', $result);
        self::assertStringContainsString('name="[]"', $result);
    }

    #[Test]
    public function multipleAttributeIsNotAddedWhenSetToFalse(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<formvh:form.uploadedResource multiple="false" />');
        $result = (new TemplateView($context))->render();
        self::assertStringNotContainsString('multiple', $result);
        self::assertSame('<input type="file" name="" />', $result);
    }

    #[Test]
    public function multipleAndAcceptAttributesCanBeCombined(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<formvh:form.uploadedResource accept="{0: \'application/pdf\'}" multiple="true" />');
        $result = (new TemplateView($context))->render();
        self::assertStringContainsString('accept="application/pdf"', $result);
        self::assertStringContainsString('multiple="multiple"', $result);
        self::assertStringContainsString('name="[]"', $result);
    }

    /*
     * Regression test for https://forge.typo3.org/issues/109827
     * When multiple files are uploaded, each hidden resourcePointer input
     * must receive a unique id attribute instead of all sharing the same id.
     */
    #[Test]
    public function resourcePointerHaveUniqueIdsForMultipleUploadedFiles(): void
    {
        $extbaseRequest = $this->buildExtbaseRequest();

        $fileRef1 = $this->createMock(FileReference::class);
        $fileRef1->method('getUid')->willReturn(1);
        $fileRef2 = $this->createMock(FileReference::class);
        $fileRef2->method('getUid')->willReturn(2);

        /** @var ObjectStorage<FileReference> $storage */
        $storage = new ObjectStorage();
        $storage->attach($fileRef1);
        $storage->attach($fileRef2);

        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource(
            '<formvh:form.uploadedResource name="upload" id="my-field" as="resource" value="{storage}" />'
        );
        $context->getVariableProvider()->add('storage', $storage);

        $result = (new TemplateView($context))->render();

        // Each file must produce exactly one id of the form {id}-file-reference-{suffix}
        preg_match_all('/id="my-field-file-reference-[^"]*"/', $result, $idMatches);
        $ids = $idMatches[0];
        self::assertCount(2, array_unique($ids), 'Each resource pointer hidden input must have a unique id');

        // Each resource pointer name attribute must carry a unique index
        preg_match_all('/name="upload\[__submittedFiles\]\[(\d+)\]\[submittedFile\]\[resourcePointer\]"/', $result, $nameMatches);
        $names = $nameMatches[1];
        self::assertCount(2, array_unique($names), 'Each resourcePointer name must have a unique index');
    }

    private function buildExtbaseRequest(): Request
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $psr7Request = (new ServerRequest())
            ->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        return new Request($psr7Request);
    }
}
