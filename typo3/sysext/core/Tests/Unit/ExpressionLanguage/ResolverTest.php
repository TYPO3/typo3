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

namespace TYPO3\CMS\Core\Tests\Unit\ExpressionLanguage;

use Prophecy\Argument;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider;
use TYPO3\CMS\Core\ExpressionLanguage\FunctionsProvider\DefaultFunctionsProvider;
use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ResolverTest
 */
class ResolverTest extends UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;

        $cacheFrontendProphecy = $this->prophesize(PhpFrontend::class);
        $cacheFrontendProphecy->require(Argument::any())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::any(), Argument::any())->willReturn(null);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('core')->willReturn($cacheFrontendProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());

        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $corePackageProphecy = $this->prophesize(PackageInterface::class);
        $corePackageProphecy->getPackagePath()->willReturn(__DIR__ . '/../../../../../../../sysext/core/');
        $packageManagerProphecy->getActivePackages()->willReturn([
            $corePackageProphecy->reveal()
        ]);
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManagerProphecy->reveal());
    }

    /**
     * @return array
     */
    public function basicExpressionsDataHandler(): array
    {
        return [
            '1+1' => ['1+1', true],
            '1 < 2' => ['1 < 2', true],
            '2 < 1' => ['2 < 1', false],
            'true' => ['true', true],
            'false' => ['false', false],
            'true != false' => ['true != false', true],
            'true < false' => ['true < false', false],
        ];
    }

    /**
     * @test
     * @dataProvider basicExpressionsDataHandler
     * @param string $expression
     * @param mixed $expectedResult
     */
    public function basicExpressionHandlingResultsWorksAsExpected(string $expression, $expectedResult)
    {
        $request = new ServerRequest();
        $expressionLanguageResolver = new Resolver('default', [], $request);
        self::assertSame($expectedResult, $expressionLanguageResolver->evaluate($expression));
    }

    /**
     * @return array
     */
    public function basicExpressionsWithVariablesDataHandler(): array
    {
        return [
            'var1 + var2' => ['var1 + var2', true],
            'var1 < var2' => ['var1 < var2', true],
            'var2 < var1' => ['var2 < var1', false],
            'varTrue' => ['varTrue', true],
            'varFalse' => ['varFalse', false],
            'varTrue != varFalse' => ['varTrue != varFalse', true],
            'varTrue < varFalse' => ['varTrue < varFalse', false],
        ];
    }

    /**
     * @test
     * @dataProvider basicExpressionsWithVariablesDataHandler
     * @param string $expression
     * @param mixed $expectedResult
     */
    public function basicExpressionHandlingWithCustomVariablesWorksAsExpected(string $expression, $expectedResult)
    {
        $contextProphecy = $this->prophesize(DefaultProvider::class);
        $contextProphecy->getExpressionLanguageProviders()->willReturn([]);
        $contextProphecy->getExpressionLanguageVariables()->willReturn([
            'var1' => '1',
            'var2' => '2',
            'varTrue' => true,
            'varFalse' => false,
         ]);
        $request = new ServerRequest();
        GeneralUtility::addInstance(DefaultProvider::class, $contextProphecy->reveal());
        $expressionLanguageResolver = new Resolver('default', [], $request);
        self::assertSame($expectedResult, $expressionLanguageResolver->evaluate($expression));
    }

    /**
     * @return array
     */
    public function basicExpressionsWithVariablesAndExpressionLanguageProviderDataHandler(): array
    {
        return [
            'testMeLowercase(var1) == var2' => ['testMeLowercase(var1) == var2', true],
            'testMeLowercase(var2) == var1' => ['testMeLowercase(var2) == var1', false],
            'testMeLowercase(var1) == var1' => ['testMeLowercase(var1) == var1', false],
            'testMeLowercase(var2) == var2' => ['testMeLowercase(var2) == var2', true],
        ];
    }

    /**
     * @test
     * @dataProvider basicExpressionsWithVariablesAndExpressionLanguageProviderDataHandler
     * @param string $expression
     * @param mixed $expectedResult
     */
    public function basicExpressionHandlingWithCustomVariablesAndExpressionLanguageProviderWorksAsExpected(string $expression, $expectedResult)
    {
        $expressionProvider = $this->prophesize(DefaultFunctionsProvider::class);
        $expressionProvider->getFunctions()->willReturn([
            new ExpressionFunction('testMeLowercase', function ($str) {
                return sprintf('(is_string(%1$s) ? strtolower(%1$s) : %1$s)', $str);
            }, function ($arguments, $str) {
                return is_string($str) ? strtolower($str) : $str;
            })
        ]);
        $contextProphecy = $this->prophesize(DefaultProvider::class);
        $contextProphecy->getExpressionLanguageProviders()->willReturn([DefaultFunctionsProvider::class]);
        $contextProphecy->getExpressionLanguageVariables()->willReturn([
            'var1' => 'FOO',
            'var2' => 'foo'
         ]);
        $request = new ServerRequest();
        GeneralUtility::addInstance(DefaultProvider::class, $contextProphecy->reveal());
        GeneralUtility::addInstance(DefaultFunctionsProvider::class, $expressionProvider->reveal());
        $expressionLanguageResolver = new Resolver('default', [], $request);
        self::assertSame($expectedResult, $expressionLanguageResolver->evaluate($expression));
    }
}
