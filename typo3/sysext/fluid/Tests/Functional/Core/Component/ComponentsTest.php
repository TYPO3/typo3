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

namespace TYPO3\CMS\Fluid\Tests\Functional\Core\Component;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Fluid\View\FluidViewFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ComponentsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/components_test',
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/components_override_test',
    ];

    public static function registeredComponentsCanBeCalledDataProvider(): array
    {
        return [
            'component' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components}<test:testComponent registeredArg="foo" />',
                "\n\n" . json_encode(['registeredArg' => 'foo']) . "\n",
            ],
            'component with additional arguments allowed' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\ComponentsAdditionalArguments}<test:testComponent registeredArg="foo" unregistered="bar" />',
                "\n\n" . json_encode(['registeredArg' => 'foo', 'unregistered' => 'bar']) . "\n",
            ],
            'overwritten component via template paths' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components}<test:toBeOverwritten onlyInOverwritten="foo" />',
                "\n\noverwritten component\n",
            ],
            'component in different folder structure' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\AlternativeStructure}<test:alternativeStructureComponent />',
                "test component in alternative folder structure\n",
            ],
            'component with modified definition by event' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components}<test:modifiedComponent initialArgument="foo" addedArgument="bar" />',
                "\n\n" . json_encode(['initialArgument' => 'foo', 'addedArgument' => 'bar']) . "\n",
            ],
            'component can access provided static variables' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components}<test:staticVariables />',
                json_encode(['staticVariable' => 'foo']) . "\n",
            ],
            'extended component renderer' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components}<test:extendedRenderer />',
                "Argument from event: |foo|\nSlot from event: |<b>bar</b>|\n",
            ],
            'alternative component renderer' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components}<test:alternativeRenderer registeredArg="foo"><b>slot content</b></test:alternativeRenderer>',
                json_encode(['arguments' => ['registeredArg' => 'foo'], 'slots' => ['default' => '<b>slot content</b>'], 'requestAttribute' => 'exampleRequestAttributeValue']),
            ],
            'class-based component collection' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components\\ClassBasedComponentCollection}<test:classBasedComponent />',
                "component configured with the default approach by Fluid Standalone\n",
            ],
            'declarative collection overwrites class-based collection' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components\\LegacyComponentCollection}<test:testComponent registeredArg="foo" />',
                "\n\n" . json_encode(['registeredArg' => 'foo']) . "\n",
            ],
        ];
    }

    #[Test]
    #[DataProvider('registeredComponentsCanBeCalledDataProvider')]
    public function registeredComponentsCanBeCalled(string $template, string $expectedOutput): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('exampleRequestAttribute', 'exampleRequestAttributeValue');

        /** @var FluidViewAdapter */
        $view = $this->get(FluidViewFactory::class)->create(new ViewFactoryData(request: $request));
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expectedOutput, $view->render(), 'uncached');

        /** @var FluidViewAdapter */
        $view = $this->get(FluidViewFactory::class)->create(new ViewFactoryData(request: $request));
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expectedOutput, $view->render(), 'cached');
    }

    public static function invalidComponentsThrowExceptionDataProvider(): array
    {
        return [
            'component does not allow arbitrary arguments' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components}<test:testComponent registeredArg="foo" unregistered="bar" />',
                \TYPO3Fluid\Fluid\Core\ViewHelper\Exception::class,
                1748903732,
            ],
            'component in different folder structure does not allow arbitrary arguments' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\AlternativeStructure}<test:alternativeStructureComponent unregistered="bar" />',
                \TYPO3Fluid\Fluid\Core\ViewHelper\Exception::class,
                1748903732,
            ],
            'component with modified definition by event requires addedArgument' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components}<test:modifiedComponent initialArgument="foo" />',
                \TYPO3Fluid\Fluid\Core\Parser\Exception::class,
                1237823699,
            ],
            'component from overwritten class-based collection is not available' => [
                '{namespace test=TYPO3Tests\\ComponentsTest\\Components\\LegacyComponentCollection}<test:classBasedComponent />',
                \TYPO3Fluid\Fluid\Core\Parser\Exception::class,
                1407060572,
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidComponentsThrowExceptionDataProvider')]
    public function invalidComponentsThrowException(string $template, string $expectedException, int $expectedExceptionCode): void
    {
        self::expectException($expectedException);
        self::expectExceptionCode($expectedExceptionCode);
        /** @var FluidViewAdapter */
        $view = $this->get(FluidViewFactory::class)->create(new ViewFactoryData());
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($template);
        $view->render();
    }
}
