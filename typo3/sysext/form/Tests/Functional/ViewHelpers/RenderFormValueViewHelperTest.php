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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class RenderFormValueViewHelperTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    public static function renderDataProvider(): array
    {
        return [
            'render processed value' => [
                '<formvh:renderFormValue renderable="{element}" as="var">{var.processedValue}</formvh:renderFormValue>',
                'element value',
            ],
            'uses local variable scope' => [
                '<formvh:renderFormValue renderable="{element}" as="var"></formvh:renderFormValue>{var.processedValue}',
                '',
            ],
        ];
    }

    #[DataProvider('renderDataProvider')]
    #[Test]
    public function render(string $template, string $expected): void
    {
        $this->loadDefaultYamlConfigurations();
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
        $definition = $this->buildFormDefinition();
        $runtime = $definition->bind($this->buildExtbaseRequest());

        $element = $definition->getElementByIdentifier('text-1');

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getVariableProvider()->add('element', $element);
        $context->getViewHelperVariableContainer()
            ->add(RenderRenderableViewHelper::class, 'formRuntime', $runtime);
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    private function buildExtbaseRequest(): Request
    {
        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->initializeUserSessionManager();
        $serverRequest = (new ServerRequest())
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.user', $frontendUser);

        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

        return (new Request($serverRequest))->withPluginName('Formframework');
    }

    private function buildFormDefinition(): FormDefinition
    {
        $formFactory = $this->get(ArrayFormFactory::class);
        return $formFactory->build([
            'type' => 'Form',
            'identifier' => 'test',
            'label' => 'test',
            'prototypeName' => 'standard',
            'renderables' => [
                [
                    'type' => 'Page',
                    'identifier' => 'page-1',
                    'label' => 'Page',
                    'renderables' => [
                        [
                            'type' => 'Text',
                            'identifier' => 'text-1',
                            'label' => 'Text',
                            'defaultValue' => 'element value',
                        ],
                    ],
                ],
            ],
        ], null, new ServerRequest());
    }

    private function loadDefaultYamlConfigurations(): void
    {
        $configurationManager = $this->get(ConfigurationManagerInterface::class);
        $configurationManager->setConfiguration([
            'plugin.' => [
                'tx_form.' => [
                    'settings.' => [
                        'yamlConfigurations.' => [
                            '10' => 'EXT:form/Configuration/Yaml/FormSetup.yaml',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
