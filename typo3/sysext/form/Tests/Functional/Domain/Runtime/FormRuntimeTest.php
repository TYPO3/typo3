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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Runtime;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormRuntimeTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected ArrayFormFactory $formFactory;
    protected Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadDefaultYamlConfigurations();
        $this->formFactory = $this->get(ArrayFormFactory::class);
        $this->request = $this->buildExtbaseRequest();
    }

    #[Test]
    public function renderThrowsExceptionIfFormDefinitionReturnsNoRendererClassName(): void
    {
        $formDefinition = $this->buildFormDefinition();
        $formDefinition->setOptions(['rendererClassName' => '']);
        $formRuntime = $formDefinition->bind($this->request);

        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1326095912);

        $formRuntime->render();
    }

    #[Test]
    public function renderThrowsExceptionIfRendererClassNameInstanceDoesNotImplementRendererInterface(): void
    {
        $formDefinition = $this->buildFormDefinition();
        // This must be a class available in the container without implementing RendererInterface
        $formDefinition->setOptions(['rendererClassName' => ConfigurationManagerInterface::class]);
        $formRuntime = $formDefinition->bind($this->request);

        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1326096024);

        $formRuntime->render();
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
        return $this->formFactory->build([
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
                        ],
                    ],
                ],
            ],
        ], null, new ServerRequest());
    }

    private function loadDefaultYamlConfigurations(): void
    {
        $configurationManager = $this->get(ConfigurationManagerInterface::class);
        $configurationManager->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
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
