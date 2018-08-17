<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\UnitDeprecated\Controller;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TypoScriptFrontendControllerTest extends UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|TypoScriptFrontendController
     */
    protected $subject;

    protected function setUp()
    {
        parent::setUp();
        GeneralUtility::flushInternalRuntimeCaches();
        $this->subject = $this->getAccessibleMock(TypoScriptFrontendController::class, ['dummy'], [], '', false);
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
    public function domainNameMatchesCurrentRequest($currentDomain, $domainRecord, $scriptName, $expectedResult)
    {
        $_SERVER['HTTP_HOST'] = $currentDomain;
        $_SERVER['SCRIPT_NAME'] = $scriptName;
        $this->assertEquals($expectedResult, $this->subject->domainNameMatchesCurrentRequest($domainRecord));
    }
}
