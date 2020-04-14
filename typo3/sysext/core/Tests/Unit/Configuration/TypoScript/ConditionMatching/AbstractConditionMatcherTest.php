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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration\TypoScript\ConditionMatching;

use Prophecy\Argument;
use Psr\Log\NullLogger;
use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test cases
 */
class AbstractConditionMatcherTest extends UnitTestCase
{
    protected $backupEnvironment = true;

    /**
     * @var AbstractConditionMatcher|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $conditionMatcher;

    /**
     * @var \ReflectionMethod
     */
    protected $evaluateExpressionMethod;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        require_once 'Fixtures/ConditionMatcherUserFuncs.php';

        $this->resetSingletonInstances = true;
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $coreCacheProphecy = $this->prophesize(PhpFrontend::class);
        $coreCacheProphecy->require(Argument::any())->willReturn(false);
        $coreCacheProphecy->set(Argument::any(), Argument::any())->willReturn(null);
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheFrontendProphecy->set(Argument::any(), Argument::any())->willReturn(null);
        $cacheFrontendProphecy->get('backendUtilityBeGetRootLine')->willReturn([]);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('core')->willReturn($coreCacheProphecy->reveal());
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheFrontendProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());

        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $corePackageProphecy = $this->prophesize(PackageInterface::class);
        $corePackageProphecy->getPackagePath()->willReturn(__DIR__ . '/../../../../../../../sysext/core/');
        $packageManagerProphecy->getActivePackages()->willReturn([
            $corePackageProphecy->reveal()
        ]);
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManagerProphecy->reveal());

        $this->initConditionMatcher();
    }

    protected function initConditionMatcher()
    {
        // test the abstract methods via the backend condition matcher
        $this->evaluateExpressionMethod = new \ReflectionMethod(AbstractConditionMatcher::class, 'evaluateExpression');
        $this->evaluateExpressionMethod->setAccessible(true);
        $this->conditionMatcher = new ConditionMatcher();
        $this->conditionMatcher->setLogger(new NullLogger());
    }

    /**
     * @return array
     */
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
     * @param string $expression
     * @param bool $expected
     */
    public function checkConditionMatcherForRequestFunction(string $expression, bool $expected): void
    {
        $request = (new ServerRequest())
            ->withParsedBody(['foo' => 1])
            ->withQueryParams(['foo' => 1])
            ->withCookieParams(['foo' => 1])
            ->withHeader('foo', '1');
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->initConditionMatcher();
        self::assertSame(
            $expected,
            $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, [$expression])
        );
    }

    /**
     * @return array
     */
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
     * @param string $format
     * @param int $expressionValue
     * @param bool $expected
     */
    public function checkConditionMatcherForDateFunction(string $format, int $expressionValue, bool $expected): void
    {
        $GLOBALS['SIM_EXEC_TIME'] = gmmktime(11, 4, 0, 1, 17, 1945);
        GeneralUtility::makeInstance(Context::class)
            ->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@' . $GLOBALS['SIM_EXEC_TIME'])));
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
     *
     * @return array
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
            Environment::getBackendPath() . '/index.php',
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
     *
     * @return array
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
            Environment::getBackendPath() . '/index.php',
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
     *
     * @return array
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
                false
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
        // Do not trigger proxy stuff of GeneralUtility::getIndPEnv
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']);

        GeneralUtility::setIndpEnv('REMOTE_ADDR', $actualIp);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = $devIpMask;
        $this->initConditionMatcher();
        $result = $this->evaluateExpressionMethod->invokeArgs($this->conditionMatcher, ['ip("devIP")']);
        self::assertSame($expectedResult, $result);
    }
}
