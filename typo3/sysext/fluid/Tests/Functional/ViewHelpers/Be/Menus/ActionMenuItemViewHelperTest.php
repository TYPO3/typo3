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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ActionMenuItemViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function isRenderedDataProvider(): array
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

    #[DataProvider('isRenderedDataProvider')]
    #[Test]
    public function isRendered(string $source, array $variables, string $expectation): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource($source);
        $view = new TemplateView($context);
        $view->assignMultiple($variables);
        self::assertSame($expectation, $view->render());
    }

    public static function selectedStateIsEvaluatedDataProvider(): array
    {
        return [
            'current controller and action selected despite additional request arguments' => [
                '<f:be.menus.actionMenuItem label="Start" controller="Message" action="start" />',
                'Message',
                'start',
                ['token' => 'dummyToken', 'route' => '/module/web/MessageModule', 'id' => '123'],
                '<option value="" selected="selected">Start</option>',
            ],
            'other controller and action not selected' => [
                '<f:be.menus.actionMenuItem label="List" controller="Appointment" action="list" />',
                'Message',
                'start',
                ['token' => 'dummyToken', 'route' => '/module/web/MessageModule', 'id' => '123'],
                '<option value="">List</option>',
            ],
            'matching item arguments selected' => [
                '<f:be.menus.actionMenuItem label="List" controller="Blog" action="list" arguments="{type: \'1\'}" />',
                'Blog',
                'list',
                ['token' => 'dummyToken', 'type' => '1'],
                '<option value="" selected="selected">List</option>',
            ],
            'differing item arguments not selected' => [
                '<f:be.menus.actionMenuItem label="List" controller="Blog" action="list" arguments="{type: \'2\'}" />',
                'Blog',
                'list',
                ['token' => 'dummyToken', 'type' => '1'],
                '<option value="">List</option>',
            ],
        ];
    }

    #[DataProvider('selectedStateIsEvaluatedDataProvider')]
    #[Test]
    public function selectedStateIsEvaluated(string $source, string $controllerName, string $actionName, array $requestArguments, string $expectation): void
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerName($controllerName);
        $extbaseRequestParameters->setControllerActionName($actionName);
        $extbaseRequestParameters->setArguments($requestArguments);
        $serverRequest = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource($source);
        self::assertSame($expectation, (new TemplateView($context))->render());
    }
}
