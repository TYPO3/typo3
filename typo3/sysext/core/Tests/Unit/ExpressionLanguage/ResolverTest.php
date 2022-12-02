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

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider;
use TYPO3\CMS\Core\ExpressionLanguage\FunctionsProvider\DefaultFunctionsProvider;
use TYPO3\CMS\Core\ExpressionLanguage\ProviderConfigurationLoader;
use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ResolverTest extends UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        GeneralUtility::addInstance(ProviderConfigurationLoader::class, new ProviderConfigurationLoader(
            ExtensionManagementUtilityAccessibleProxy::getPackageManager(),
            new NullFrontend('test'),
            'ExpressionLanguageProviders'
        ));
    }

    public function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

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
     */
    public function basicExpressionHandlingResultsWorksAsExpected(string $expression, bool $expectedResult): void
    {
        $expressionLanguageResolver = new Resolver('default', []);
        self::assertSame($expectedResult, $expressionLanguageResolver->evaluate($expression));
    }

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
     */
    public function basicExpressionHandlingWithCustomVariablesWorksAsExpected(string $expression, bool $expectedResult): void
    {
        $contextMock = $this->createMock(DefaultProvider::class);
        $contextMock->method('getExpressionLanguageProviders')->willReturn([]);
        $contextMock->method('getExpressionLanguageVariables')->willReturn([
            'var1' => '1',
            'var2' => '2',
            'varTrue' => true,
            'varFalse' => false,
         ]);
        GeneralUtility::addInstance(DefaultProvider::class, $contextMock);
        $expressionLanguageResolver = new Resolver('default', []);
        self::assertSame($expectedResult, $expressionLanguageResolver->evaluate($expression));
    }

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
     * @param mixed $expectedResult
     */
    public function basicExpressionHandlingWithCustomVariablesAndExpressionLanguageProviderWorksAsExpected(string $expression, $expectedResult): void
    {
        $expressionProviderMock = $this->createMock(DefaultFunctionsProvider::class);
        $expressionProviderMock->method('getFunctions')->willReturn([
            new ExpressionFunction('testMeLowercase', static function ($str) {
                return sprintf('(is_string(%1$s) ? strtolower(%1$s) : %1$s)', $str);
            }, static function ($arguments, $str) {
                return is_string($str) ? strtolower($str) : $str;
            }),
        ]);
        $contextMock = $this->createMock(DefaultProvider::class);
        $contextMock->method('getExpressionLanguageProviders')->willReturn([DefaultFunctionsProvider::class]);
        $contextMock->method('getExpressionLanguageVariables')->willReturn([
            'var1' => 'FOO',
            'var2' => 'foo',
         ]);
        GeneralUtility::addInstance(DefaultProvider::class, $contextMock);
        GeneralUtility::addInstance(DefaultFunctionsProvider::class, $expressionProviderMock);
        $expressionLanguageResolver = new Resolver('default', []);
        self::assertSame($expectedResult, $expressionLanguageResolver->evaluate($expression));
    }
}
