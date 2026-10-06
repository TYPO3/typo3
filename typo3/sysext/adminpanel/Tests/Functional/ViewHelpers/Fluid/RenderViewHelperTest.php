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

namespace TYPO3\CMS\Adminpanel\Tests\Functional\ViewHelpers\Fluid;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\Authentication\FrontendBackendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class RenderViewHelperTest extends FunctionalTestCase
{
    private const TEMPLATE = '<f:section name="Main">content</f:section><f:render section="Main" />';

    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = ['adminpanel'];

    protected function setUp(): void
    {
        parent::setUp();
        $backendUser = new FrontendBackendUserAuthentication();
        $backendUser->uc['AdminPanel']['preview_showFluidDebug'] = '1';
        $GLOBALS['BE_USER'] = $backendUser;
    }

    /**
     * The debug flag stays in the user configuration after the admin panel
     * is closed. As the page cache is only disabled while the panel is
     * active, rendering debug output then would end up in the page cache.
     */
    #[Test]
    public function debugOutputIsNotRenderedIfAdminPanelIsNotActive(): void
    {
        self::assertSame('content', $this->render($this->createFrontendRequest()));
    }

    #[Test]
    public function debugOutputIsRenderedIfFluidDebugIsActivatedForRequest(): void
    {
        $request = $this->createFrontendRequest()->withAttribute('admin-panel.fluid.debug', true);
        self::assertStringContainsString('class="t3js-debug-template"', $this->render($request));
    }

    private function createFrontendRequest(): ServerRequest
    {
        return new ServerRequest('https://example.com/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }

    private function render(ServerRequest $request): string
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource(self::TEMPLATE);
        return trim((string)new TemplateView($context)->render());
    }
}
