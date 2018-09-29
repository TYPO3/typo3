<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Unit\Compatibility;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Frontend\Compatibility\LegacyDomainResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LegacyDomainResolverTest extends UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|LegacyDomainResolver
     */
    protected $subject;

    protected $resetSingletonInstances = true;

    protected $backupEnvironment = true;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(LegacyDomainResolver::class, ['dummy'], [], '', false);
    }

    /**
     * Tests concerning domainNameMatchesCurrentRequest
     */

    /**
     * @return array
     */
    public function domainNameMatchesCurrentRequestDataProvider()
    {
        return [
            'same domains' => [
                'typo3.org',
                'typo3.org',
                '/index.php',
                true,
            ],
            'same domains with subdomain' => [
                'www.typo3.org',
                'www.typo3.org',
                '/index.php',
                true,
            ],
            'different domains' => [
                'foo.bar',
                'typo3.org',
                '/index.php',
                false,
            ],
            'domain record with script name' => [
                'typo3.org',
                'typo3.org/foo/bar',
                '/foo/bar/index.php',
                true,
            ],
            'domain record with wrong script name' => [
                'typo3.org',
                'typo3.org/foo/bar',
                '/bar/foo/index.php',
                false,
            ],
        ];
    }

    /**
     * @param string $currentDomain
     * @param string $domainRecord
     * @param string $scriptName
     * @param bool $expectedResult
     * @test
     * @dataProvider domainNameMatchesCurrentRequestDataProvider
     */
    public function domainNameMatchesCurrentRequest(string $currentDomain, string $domainRecord, string $scriptName, bool $expectedResult)
    {
        $_SERVER['HTTP_HOST'] = $currentDomain;
        $_SERVER['SCRIPT_NAME'] = $scriptName;
        $request = ServerRequestFactory::fromGlobals();
        $normalizedParams = new NormalizedParams($_SERVER, [], Environment::getCurrentScript(), Environment::getPublicPath());
        $request = $request->withAttribute('normalizedParams', $normalizedParams);
        $this->assertEquals($expectedResult, $this->subject->_call('domainNameMatchesCurrentRequest', $domainRecord, $request));
    }
}
