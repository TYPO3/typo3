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

namespace TYPO3\CMS\Backend\Tests\Functional\View;

use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendViewFactoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/backend/Tests/Functional/View/Fixtures/Extensions/test_templates_a',
        'typo3/sysext/backend/Tests/Functional/View/Fixtures/Extensions/test_templates_b',
        'typo3/sysext/backend/Tests/Functional/View/Fixtures/Extensions/test_templates_c',
    ];

    /**
     * @test
     */
    public function createUsesTemplatePathsWithPackageGivenAsRouteOption()
    {
        $request = (new ServerRequest())->withAttribute('route', new Route('testing', ['packageName' => 'typo3/cms-test-templates-a']));
        $subject = $this->get(BackendViewFactory::class);
        $view = $subject->create($request);
        $result = $view->render('Foo');
        self::assertStringContainsString('Foo template from extension test_templates_a', $result);
        self::assertStringContainsString('Foo layout from extension test_templates_a', $result);
        self::assertStringContainsString('Foo partial from extension test_templates_a', $result);
    }

    /**
     * @test
     */
    public function createUsesTemplatePathsWithPackageGivenAsArgument()
    {
        $request = (new ServerRequest())->withAttribute('route', new Route('testing', []));
        $subject = $this->get(BackendViewFactory::class);
        $view = $subject->create($request, ['typo3/cms-test-templates-a']);
        $result = $view->render('Foo');
        self::assertStringContainsString('Foo template from extension test_templates_a', $result);
        self::assertStringContainsString('Foo layout from extension test_templates_a', $result);
        self::assertStringContainsString('Foo partial from extension test_templates_a', $result);
    }

    /**
     * @test
     */
    public function createUsesOverrideTemplatePathsWithBasePackageNameFromRoute()
    {
        $request = (new ServerRequest())->withAttribute('route', new Route('testing', ['packageName' => 'typo3/cms-test-templates-a']));
        $subject = $this->get(BackendViewFactory::class);
        $view = $subject->create($request, ['typo3/cms-test-templates-b']);
        $result = $view->render('Foo');
        self::assertStringContainsString('Foo template from extension test_templates_b', $result);
        self::assertStringContainsString('Foo layout from extension test_templates_b', $result);
        self::assertStringContainsString('Foo partial from extension test_templates_b', $result);
    }

    /**
     * @test
     */
    public function createUsesOverrideTemplatePathsWithMultiplePackagesGivenAsArgument()
    {
        $request = (new ServerRequest())->withAttribute('route', new Route('testing', []));
        $subject = $this->get(BackendViewFactory::class);
        $view = $subject->create(
            $request,
            [
                'typo3/cms-test-templates-a',
                'typo3/cms-test-templates-b',
            ]
        );
        $result = $view->render('Foo');
        self::assertStringContainsString('Foo template from extension test_templates_b', $result);
        self::assertStringContainsString('Foo layout from extension test_templates_b', $result);
        self::assertStringContainsString('Foo partial from extension test_templates_b', $result);
    }

    /**
     * @test
     */
    public function createUsesPrefersTemplateFromLastOverrideWithMultiplePackagesGivenAsArgument()
    {
        $request = (new ServerRequest())->withAttribute('route', new Route('testing', []));
        $subject = $this->get(BackendViewFactory::class);
        $view = $subject->create(
            $request,
            [
                'typo3/cms-test-templates-b',
                'typo3/cms-test-templates-a',
            ]
        );
        $result = $view->render('Foo');
        self::assertStringContainsString('Foo template from extension test_templates_a', $result);
        self::assertStringContainsString('Foo layout from extension test_templates_a', $result);
        self::assertStringContainsString('Foo partial from extension test_templates_a', $result);
    }

    /**
     * @test
     */
    public function createUsesFirstExistingFilesInChainBeginningFromLastOverrideWithMultiplePackagesGivenAsArgument()
    {
        $request = (new ServerRequest())->withAttribute('route', new Route('testing', []));
        $subject = $this->get(BackendViewFactory::class);
        $view = $subject->create(
            $request,
            [
                'typo3/cms-test-templates-b',
                'typo3/cms-test-templates-a',
                'typo3/cms-test-templates-c',
            ]
        );
        $result = $view->render('Foo');
        self::assertStringContainsString('Foo template from extension test_templates_a', $result);
        self::assertStringContainsString('Foo layout from extension test_templates_a', $result);
        self::assertStringContainsString('Foo partial from extension test_templates_a', $result);
    }

    /**
     * @test
     */
    public function createAllowsOverridesUsingTsConfig()
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendViewFactoryTestPages.csv');
        $request = (new ServerRequest())
            ->withAttribute('route', new Route('testing', ['packageName' => 'typo3/cms-test-templates-a']))
            ->withQueryParams(['id' => 1]);
        $subject = $this->get(BackendViewFactory::class);
        $view = $subject->create($request);
        $result = $view->render('Foo');
        self::assertStringContainsString('Foo template from extension test_templates_b', $result);
        self::assertStringContainsString('Foo layout from extension test_templates_b', $result);
        self::assertStringContainsString('Foo partial from extension test_templates_b', $result);
    }

    /**
     * @test
     */
    public function createAllowsOverridesUsingTsConfigUsesFirstExistingFilesInChain()
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendViewFactoryTestPagesWithFallback.csv');
        $request = (new ServerRequest())
            ->withAttribute('route', new Route('testing', ['packageName' => 'typo3/cms-test-templates-a']))
            ->withQueryParams(['id' => 1]);
        $subject = $this->get(BackendViewFactory::class);
        $view = $subject->create($request);
        $result = $view->render('Foo');
        self::assertStringContainsString('Foo template from extension test_templates_b', $result);
        self::assertStringContainsString('Foo layout from extension test_templates_b', $result);
        self::assertStringContainsString('Foo partial from extension test_templates_b', $result);
    }
}
