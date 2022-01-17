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

namespace TYPO3\CMS\Form\Tests\Functional\ViewHelpers;

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class UploadedResourceViewHelperTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['form'];

    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    /**
     * @test
     */
    public function accpetAttributeIsAdded(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<formvh:form.uploadedResource accept="{0: \'image/jpeg\', 1: \'image/png\'}"/>');
        self::assertSame('<input accept="image/jpeg,image/png" type="file" name="" />', (new TemplateView($context))->render());
    }
}
