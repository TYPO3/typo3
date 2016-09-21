<?php
declare(strict_types=1);
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Web;

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

use TYPO3\CMS\Extbase\Mvc\Web\CacheHashEnforcer;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Test case
 */
class CacheHashEnforcerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\CacheHashEnforcer
     */
    protected $subject;

    /**
     * @var TypoScriptFrontendController|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendControllerMock;

    protected function setUp()
    {
        $this->frontendControllerMock = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();
        $this->frontendControllerMock->id = 42;
        $cacheHashCalculator = new CacheHashCalculator();
        $this->subject = new CacheHashEnforcer(
            $cacheHashCalculator,
            $this->frontendControllerMock
        );
    }

    /**
     * @test
     */
    public function validateCallsReqCHashIfRequestArgumentsArePresent()
    {
        $request = new Request();
        $request->setArguments(['foo' => 'bar']);
        $this->frontendControllerMock
            ->expects($this->once())
            ->method('reqCHash');

        $this->subject->enforceForRequest($request, 'tx_foo');
    }

    /**
     * @test
     */
    public function validateDoesNotCallsReqCHashIfNoRequestArgumentsArePresent()
    {
        $request = new Request();
        $this->frontendControllerMock
            ->expects($this->never())
            ->method('reqCHash');

        $this->subject->enforceForRequest($request, 'tx_foo');
    }
}
