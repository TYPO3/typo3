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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Be\Menus;

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class ActionMenuItemViewHelperTest extends FunctionalTestCase
{
    public function isRenderedDataProvider(): array
    {
        return [
            'tag syntax' => [
                '<f:be.menus.actionMenuItem label="{label}" controller="{controller}" action="{action}" />',
                [
                    'label' => 'label<>&"\'',
                    'controller' => 'controller<>&"\'',
                    'action' => 'action<>&"\'',
                ],
                '<option value="">label&lt;&gt;&amp;&quot;&#039;</option>',
            ],
            'inline syntax' => [
                '{f:be.menus.actionMenuItem(label:label, controller:controller, action:action)}',
                [
                    'label' => 'label<>&"\'',
                    'controller' => 'controller<>&"\'',
                    'action' => 'action<>&"\'',
                ],
                '<option value="">label&lt;&gt;&amp;&quot;&#039;</option>',
            ],
            'inline syntax with quotes' => [
                '{f:be.menus.actionMenuItem(label:\'{label}\', controller:\'{controller}\', action:\'{action}\')}',
                [
                    'label' => 'label<>&"\'',
                    'controller' => 'controller<>&"\'',
                    'action' => 'action<>&"\'',
                ],
                '<option value="">label&lt;&gt;&amp;&quot;&#039;</option>',
            ],
        ];
    }

    /**
     * @param string $source
     * @param array $variables
     * @param string $expectation
     *
     * @test
     * @dataProvider isRenderedDataProvider
     */
    public function isRendered(string $source, array $variables, string $expectation): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($source);
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;
        $context->setRequest(new Request($serverRequest));
        $view = new TemplateView($context);
        $view->assignMultiple($variables);
        self::assertSame($expectation, $view->render());
    }
}
