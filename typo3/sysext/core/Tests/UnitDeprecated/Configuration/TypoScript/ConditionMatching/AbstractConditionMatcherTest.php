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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Configuration\TypoScript\ConditionMatching;

use Psr\Log\NullLogger;
use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider;
use TYPO3\CMS\Core\ExpressionLanguage\ProviderConfigurationLoader;
use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractConditionMatcherTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;
    protected bool $resetSingletonInstances = true;
    protected ConditionMatcher $conditionMatcher;
    protected ?\ReflectionMethod $evaluateExpressionMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $cacheManager = new CacheManager();
        $cacheManager->registerCache(new NullFrontend('core'));
        $cacheManager->registerCache(new NullFrontend('runtime'));
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);

        $packageManagerMock = $this->createMock(PackageManager::class);
        $corePackageMock = $this->createMock(PackageInterface::class);
        $corePackageMock->method('getPackagePath')->willReturn(__DIR__ . '/../../../../../../../sysext/core/');
        $packageManagerMock->method('getActivePackages')->willReturn([
            $corePackageMock,
        ]);
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManagerMock);

        $this->initConditionMatcher();
    }

    protected function initConditionMatcher(): void
    {
        GeneralUtility::addInstance(ProviderConfigurationLoader::class, new ProviderConfigurationLoader(
            GeneralUtility::makeInstance(PackageManager::class),
            new NullFrontend('testing'),
            'ExpressionLanguageProviders'
        ));
        // test the abstract methods via the backend condition matcher
        $this->evaluateExpressionMethod = new \ReflectionMethod(AbstractConditionMatcher::class, 'evaluateExpression');
        $defaultProvider = new DefaultProvider(new Typo3Version(), new Context(), new Features());
        GeneralUtility::addInstance(DefaultProvider::class, $defaultProvider);
        $this->conditionMatcher = new ConditionMatcher();
        $this->conditionMatcher->setLogger(new NullLogger());
    }

    public function requestFunctionDataProvider(): array
    {
        return [
            // GET tests
            // getQueryParams()
            'request.getQueryParams()[\'foo\'] > 0' => ['request.getQueryParams()[\'foo\'] > 0', true],
            'request.getQueryParams()[\'foo\'][\'bar\'] > 0' => ['request.getQueryParams()[\'foo\'][\'bar\'] > 0', false],
            'request.getQueryParams()[\'bar\'][\'foo\'] > 0' => ['request.getQueryParams()[\'bar\'][\'foo\'] > 0', false],
            'request.getQueryParams()[\'foo\'] == 0' => ['request.getQueryParams()[\'foo\'] == 0', false],
            'request.getQueryParams()[\'foo\'][\'bar\'] == 0' => ['request.getQueryParams()[\'foo\'][\'bar\'] == 0', false],
            // POST tests
            // getParsedBody()
            'request.getParsedBody()[\'foo\'] > 0' => ['request.getParsedBody()[\'foo\'] > 0', true],
            'request.getParsedBody()[\'foo\'][\'bar\'] > 0' => ['request.getParsedBody()[\'foo\'][\'bar\'] > 0', false],
            'request.getParsedBody()[\'bar\'][\'foo\'] > 0' => ['request.getParsedBody()[\'bar\'][\'foo\'] > 0', false],
            'request.getParsedBody()[\'foo\'] == 0' => ['request.getParsedBody()[\'foo\'] == 0', false],
            'request.getParsedBody()[\'foo\'][\'bar\'] == 0' => ['request.getParsedBody()[\'foo\'][\'bar\'] == 0', false],
            // HEADERS tests
            // getHeaders()
            'request.getHeaders()[\'foo\'] == [\'1\']' => ['request.getHeaders()[\'foo\'] == [\'1\']', true],
            'request.getHeaders()[\'foo\'] == [\'0\']' => ['request.getHeaders()[\'foo\'] == [\'0\']', false],
            'request.getHeaders()[\'foo\'] == [\'bar\']' => ['request.getHeaders()[\'foo\'] == [\'bar\']', false],
            // COOKIES tests
            // getCookieParams()
            'request.getCookieParams()[\'foo\'] > 0' => ['request.getCookieParams()[\'foo\'] > 0', true],
            'request.getCookieParams()[\'foo\'] > 1' => ['request.getCookieParams()[\'foo\'] > 1', false],
        ];
    }

    /**
     * @test
     * @dataProvider requestFunctionDataProvider
     */
    public function checkConditionMatcherForRequestFunction(string $expression, bool $expected): void
    {
        $request = (new ServerRequest())
            ->withParsedBody(['foo' => 1])
            ->withQueryParams(['foo' => 1])
            ->withCookieParams(['foo' => 1])
            ->withHeader('foo', '1')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->initConditionMatcher();
        self::assertSame(
            $expected,
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, [$expression])
        );
    }

    public function datesFunctionDataProvider(): array
    {
        return [
            '[dayofmonth = 17]' => ['j', 17, true],
            '[dayofweek = 3]' => ['w', 3, true],
            '[dayofyear = 16]' => ['z', 16, true],
            '[hour = 11]' => ['G', 11, true],
            '[minute = 4]' => ['i', 4, true],
            '[month = 1]' => ['n', 1, true],
            '[year = 1945]' => ['Y', 1945, true],
        ];
    }

    /**
     * @test
     * @dataProvider datesFunctionDataProvider
     */
    public function checkConditionMatcherForDateFunction(string $format, int $expressionValue, bool $expected): void
    {
        $GLOBALS['SIM_EXEC_TIME'] = gmmktime(11, 4, 0, 1, 17, 1945);
        GeneralUtility::makeInstance(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@' . $GLOBALS['SIM_EXEC_TIME'])));
        $this->initConditionMatcher();
        self::assertSame(
            $expected,
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['date("' . $format . '") == ' . $expressionValue])
        );
    }

    /**
     * @test
     */
    public function checkConditionMatcherForFeatureFunction(): void
    {
        $featureName = 'test.testFeature';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features'][$featureName] = true;
        self::assertTrue(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['feature("' . $featureName . '")'])
        );
        self::assertTrue(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['feature("' . $featureName . '") == true'])
        );
        self::assertTrue(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['feature("' . $featureName . '") === true'])
        );
        self::assertFalse(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['feature("' . $featureName . '") == false'])
        );
        self::assertFalse(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['feature("' . $featureName . '") === false'])
        );

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features'][$featureName] = false;
        self::assertFalse(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['feature("' . $featureName . '")'])
        );
        self::assertFalse(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['feature("' . $featureName . '") == true'])
        );
        self::assertFalse(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['feature("' . $featureName . '") === true'])
        );
        self::assertTrue(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['feature("' . $featureName . '") == false'])
        );
        self::assertTrue(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['feature("' . $featureName . '") === false'])
        );
    }

    /**
     * Data provider with matching applicationContext conditions.
     */
    public function matchingApplicationContextConditionsDataProvider(): array
    {
        return [
            ['Production*'],
            ['Production/Staging/*'],
            ['Production/Staging/Server2'],
            ['/^Production.*$/'],
            ['/^Production\\/.+\\/Server\\d+$/'],
        ];
    }

    /**
     * @test
     * @dataProvider matchingApplicationContextConditionsDataProvider
     */
    public function evaluateConditionCommonReturnsTrueForMatchingContexts($matchingContextCondition): void
    {
        Environment::initialize(
            new ApplicationContext('Production/Staging/Server2'),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );

        $this->initConditionMatcher();

        // Test expression language
        self::assertTrue(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['like(applicationContext, "' . preg_quote($matchingContextCondition, '/') . '")'])
        );
    }

    /**
     * Data provider with not matching applicationContext conditions.
     */
    public function notMatchingApplicationContextConditionsDataProvider(): array
    {
        return [
            ['Production'],
            ['Testing*'],
            ['Development/Profiling, Testing/Unit'],
            ['Testing/Staging/Server2'],
            ['/^Testing.*$/'],
            ['/^Production\\/.+\\/Host\\d+$/'],
        ];
    }

    /**
     * @test
     * @dataProvider notMatchingApplicationContextConditionsDataProvider
     */
    public function evaluateConditionCommonReturnsNullForNotMatchingApplicationContexts($notMatchingApplicationContextCondition): void
    {
        Environment::initialize(
            new ApplicationContext('Production/Staging/Server2'),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $this->initConditionMatcher();

        // Test expression language
        self::assertFalse(
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['like(applicationContext, "' . preg_quote($notMatchingApplicationContextCondition, '/') . '")'])
        );
    }

    /**
     * Data provider for evaluateConditionCommonEvaluatesIpAddressesCorrectly
     */
    public function evaluateConditionCommonDevIpMaskDataProvider(): array
    {
        return [
            // [0] $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
            // [1] Actual IP
            // [2] Expected condition result
            'IP matches' => [
                '127.0.0.1',
                '127.0.0.1',
                true,
            ],
            'ipv4 wildcard subnet' => [
                '127.0.0.1/24',
                '127.0.0.2',
                true,
            ],
            'ipv6 wildcard subnet' => [
                '0:0::1/128',
                '::1',
                true,
            ],
            'List of addresses matches' => [
                '1.2.3.4, 5.6.7.8',
                '5.6.7.8',
                true,
            ],
            'IP does not match' => [
                '127.0.0.1',
                '127.0.0.2',
                false,
            ],
            'ipv4 subnet does not match' => [
                '127.0.0.1/8',
                '126.0.0.1',
                false,
            ],
            'ipv6 subnet does not match' => [
                '::1/127',
                '::2',
                false,
            ],
            'List of addresses does not match' => [
                '127.0.0.1, ::1',
                '::2',
                false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider evaluateConditionCommonDevIpMaskDataProvider
     */
    public function evaluateConditionCommonEvaluatesIpAddressesCorrectly($devIpMask, $actualIp, $expectedResult): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = $devIpMask;

        $request = new ServerRequest(
            new Uri(''),
            'GET',
            'php://input',
            [],
            [
                'REMOTE_ADDR' => $actualIp,
            ]
        );
        $normalizedParams = NormalizedParams::createFromRequest($request);
        $request = $request->withAttribute('normalizedParams', $normalizedParams);

        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->initConditionMatcher();
        $result = $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['ip("devIP")']);
        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function typoScriptElseConditionIsNotEvaluatedAndAlwaysReturnsFalse(): void
    {
        $this->initConditionMatcher();
        $expressionProperty = new \ReflectionProperty(AbstractConditionMatcher::class, 'expressionLanguageResolver');
        $resolverMock = $this->createMock(Resolver::class);
        $resolverMock->expects(self::never())->method('evaluate')->withAnyParameters();
        $expressionProperty->setValue($this->conditionMatcher, $resolverMock);
        self::assertFalse($this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['ELSE']));
    }
}
