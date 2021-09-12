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

namespace TYPO3\CMS\Core\Tests\Unit\Session;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for the SessionManager
 */
class SessionManagerTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function getSessionBackendUsesDefaultBackendFromConfiguration(): void
    {
        $subject = new SessionManager();
        self::assertInstanceOf(DatabaseSessionBackend::class, $subject->getSessionBackend('BE'));
    }

    /**
     * @test
     */
    public function getSessionBackendReturnsExpectedSessionBackendBasedOnConfiguration(): void
    {
        $backendProphecy = $this->prophesize(SessionBackendInterface::class);
        $backendRevelation = $backendProphecy->reveal();
        $backendClassName = get_class($backendRevelation);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['myidentifier'] = [
            'backend'  => $backendClassName,
            'options' => []
        ];
        $backendProphecy->initialize(Argument::cetera())->shouldBeCalled();
        $backendProphecy->validateConfiguration(Argument::cetera())->shouldBeCalled();
        GeneralUtility::addInstance($backendClassName, $backendRevelation);
        $subject = new SessionManager();
        self::assertInstanceOf($backendClassName, $subject->getSessionBackend('myidentifier'));
    }

    /**
     * @test
     */
    public function getSessionBackendThrowsExceptionForMissingConfiguration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1482234750);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['myNewIdentifier'] = 'I am not an array';
        $subject = new SessionManager();
        $subject->getSessionBackend('myNewidentifier');
    }

    /**
     * @test
     */
    public function getSessionBackendThrowsExceptionIfBackendDoesNotImplementInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1482235035);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['myidentifier'] = [
            'backend'  => \stdClass::class,
            'options' => []
        ];
        (new SessionManager())->getSessionBackend('myidentifier');
    }
}
