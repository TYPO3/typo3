<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\SelectTreeController;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class SelectTreeControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function fetchDataActionThrowsExceptionIfTcaOfTableDoesNotExist()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1479386729);
        (new SelectTreeController())->fetchDataAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function fetchDataActionThrowsExceptionIfTcaOfTableFieldDoesNotExist()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getQueryParams()->shouldBeCalled()->willReturn([
            'table' => 'aTable',
            'field' => 'aField',
        ]);
        $GLOBALS['TCA']['aTable']['columns'] = [];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1479386990);
        (new SelectTreeController())->fetchDataAction($requestProphecy->reveal(), $responseProphecy->reveal());
    }
}
