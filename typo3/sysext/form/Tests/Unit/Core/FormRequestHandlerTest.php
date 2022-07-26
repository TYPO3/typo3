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

namespace TYPO3\CMS\Form\Tests\Unit\Core;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Form\Core\FormRequestHandler;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormRequestHandlerTest extends UnitTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @var bool
     */
    protected $resetSingletonInstances = true;
    private ObjectProphecy $formPersistenceManagerProphecy;
    private ObjectProphecy $arrayFormFactoryProphecy;
    private ObjectProphecy $configurationManagerProphecy;
    private ObjectProphecy $contentObjectRendererProphecy;
    private ObjectProphecy $serverRequestProphecy;
    private ObjectProphecy $extbaseRequestProphecy;
    private FormRequestHandler $subject;

    public function setUp(): void
    {
        parent::setUp();
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $extbaseRequestBuilderProphecy = $this->prophesize(RequestBuilder::class);
        $this->arrayFormFactoryProphecy = $this->prophesize(ArrayFormFactory::class);
        $this->formPersistenceManagerProphecy = $this->prophesize(FormPersistenceManagerInterface::class);
        $this->configurationManagerProphecy = $this->prophesize(ConfigurationManagerInterface::class);
        $this->contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);
        $this->serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->extbaseRequestProphecy = $this->prophesize(ServerRequestInterface::class);

        $containerProphecy->has(ArrayFormFactory::class)->willReturn(true);
        $containerProphecy->get(ArrayFormFactory::class)->willReturn($this->arrayFormFactoryProphecy->reveal());
        $extbaseRequestBuilderProphecy->build(Argument::any())->willReturn($this->extbaseRequestProphecy->reveal());

        $this->subject = new FormRequestHandler(
            $containerProphecy->reveal(),
            $this->formPersistenceManagerProphecy->reveal(),
            $extbaseRequestBuilderProphecy->reveal(),
            $this->configurationManagerProphecy->reveal()
        );
        $this->subject->setContentObjectRenderer($this->contentObjectRendererProphecy->reveal());
    }

    public function processRespectsPrototypeNameDataProvider(): \Generator
    {
        yield 'only formDefinition-override with existing prototypeName-property without prototypeName-override' => [
            'contentObjectConfiguration' => [
                'configuration' => [
                    'prototypeName' => 'standard',
                ],
            ],
            'persistedFormDefinition' => null,
            'expectedPrototypeName' => 'standard',
        ];

        yield 'only formDefinition-override without existing prototypeName-property without prototypeName-override' => [
            'contentObjectConfiguration' => [
                'configuration' => [
                ],
            ],
            'persistedFormDefinition' => null,
            'expectedPrototypeName' => 'standard',
        ];

        yield 'only formDefinition-override with existing prototypeName-property with prototypeName-override' => [
            'contentObjectConfiguration' => [
                'configuration' => [
                    'prototypeName' => 'standard',
                ],
                'prototypeName' => 'alernate',
            ],
            'persistedFormDefinition' => null,
            'expectedPrototypeName' => 'alernate',
        ];

        yield 'only formDefinition-override without existing prototypeName-property with prototypeName-override' => [
            'contentObjectConfiguration' => [
                'configuration' => [
                ],
                'prototypeName' => 'alernate',
            ],
            'persistedFormDefinition' => null,
            'expectedPrototypeName' => 'alernate',
        ];

        yield 'persisted form definition with existing prototypeName-property without formDefinition-override without prototypeName-override' => [
            'contentObjectConfiguration' => [
                'persistenceIdentifier' => '1:/xxx.form.yaml',
            ],
            'persistedFormDefinition' => [
                'prototypeName' => 'standard',
            ],
            'expectedPrototypeName' => 'standard',
        ];

        yield 'persisted form definition without existing prototypeName-property without formDefinition-override without prototypeName-override' => [
            'contentObjectConfiguration' => [
                'persistenceIdentifier' => '1:/xxx.form.yaml',
            ],
            'persistedFormDefinition' => [],
            'expectedPrototypeName' => 'standard',
        ];

        yield 'persisted form definition with existing prototypeName-property without formDefinition-override with prototypeName-override' => [
            'contentObjectConfiguration' => [
                'persistenceIdentifier' => '1:/xxx.form.yaml',
                'prototypeName' => 'alernate',
            ],
            'persistedFormDefinition' => [
                'prototypeName' => 'standard',
            ],
            'expectedPrototypeName' => 'alernate',
        ];

        yield 'persisted form definition without existing prototypeName-property without formDefinition-override with prototypeName-override' => [
            'contentObjectConfiguration' => [
                'persistenceIdentifier' => '1:/xxx.form.yaml',
                'prototypeName' => 'alernate',
            ],
            'persistedFormDefinition' => [],
            'expectedPrototypeName' => 'alernate',
        ];

        yield 'persisted form definition with existing prototypeName-property with formDefinition-override with existing prototypeName-property without prototypeName-override' => [
            'contentObjectConfiguration' => [
                'persistenceIdentifier' => '1:/xxx.form.yaml',
                'configuration' => [
                    'prototypeName' => 'alternate',
                ],
            ],
            'persistedFormDefinition' => [
                'prototypeName' => 'standard',
            ],
            'expectedPrototypeName' => 'alternate',
        ];

        yield 'persisted form definition without existing prototypeName-property with formDefinition-override with existing prototypeName-property without prototypeName-override' => [
            'contentObjectConfiguration' => [
                'persistenceIdentifier' => '1:/xxx.form.yaml',
                'configuration' => [
                    'prototypeName' => 'alternate',
                ],
            ],
            'persistedFormDefinition' => [],
            'expectedPrototypeName' => 'alternate',
        ];

        yield 'persisted form definition with existing prototypeName-property with formDefinition-override with existing prototypeName-property with prototypeName-override' => [
            'contentObjectConfiguration' => [
                'persistenceIdentifier' => '1:/xxx.form.yaml',
                'configuration' => [
                    'prototypeName' => 'alternate',
                ],
                'prototypeName' => 'some-other-alternate',
            ],
            'persistedFormDefinition' => [
                'prototypeName' => 'standard',
            ],
            'expectedPrototypeName' => 'some-other-alternate',
        ];

        yield 'persisted form definition without existing prototypeName-property with formDefinition-override with existing prototypeName-property with prototypeName-override' => [
            'contentObjectConfiguration' => [
                'persistenceIdentifier' => '1:/xxx.form.yaml',
                'configuration' => [
                    'prototypeName' => 'alternate',
                ],
                'prototypeName' => 'some-other-alternate',
            ],
            'persistedFormDefinition' => [],
            'expectedPrototypeName' => 'some-other-alternate',
        ];
    }

    /**
     * @test
     * @dataProvider processRespectsPrototypeNameDataProvider
     */
    public function processRespectsPrototypeName(array $contentObjectConfiguration, ?array $persistedFormDefinition, string $expectedPrototypeName): void
    {
        $this->contentObjectRendererProphecy->getUserObjectType()->willReturn(ContentObjectRenderer::OBJECTTYPE_USER);
        $this->contentObjectRendererProphecy->convertToUserIntObject()->shouldBeCalled();

        $this->configurationManagerProphecy->setContentObject(Argument::any())->shouldBeCalled();
        $this->configurationManagerProphecy->setConfiguration(Argument::cetera())->shouldBeCalled();

        $this->extbaseRequestProphecy->getAttribute('extbase')->willReturn(new ExtbaseRequestParameters());
        $this->extbaseRequestProphecy->getMethod()->willReturn('POST');

        $this->formPersistenceManagerProphecy->load(Argument::any())->willReturn($persistedFormDefinition);
        $this->arrayFormFactoryProphecy->build(Argument::cetera())->willReturn(new FormDefinition('test'));

        $this->arrayFormFactoryProphecy->build(Argument::any(), $expectedPrototypeName)->shouldBeCalled();
        $this->subject->process('', $contentObjectConfiguration, $this->serverRequestProphecy->reveal());
    }
}
