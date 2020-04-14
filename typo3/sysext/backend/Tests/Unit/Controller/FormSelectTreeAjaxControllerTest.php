<?php

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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\FormSelectTreeAjaxController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormSelectTreeAjaxControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function fetchDataActionThrowsExceptionIfTcaOfTableDoesNotExist()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1479386729);
        (new FormSelectTreeAjaxController())->fetchDataAction($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function fetchDataActionThrowsExceptionIfTcaOfTableFieldDoesNotExist()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getQueryParams()->shouldBeCalled()->willReturn([
            'tableName' => 'aTable',
            'fieldName' => 'aField',
        ]);
        $GLOBALS['TCA']['aTable']['columns'] = [];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1479386990);
        (new FormSelectTreeAjaxController())->fetchDataAction($requestProphecy->reveal());
    }
}
