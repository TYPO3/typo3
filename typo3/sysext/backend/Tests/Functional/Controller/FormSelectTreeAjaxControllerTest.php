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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\FormSelectTreeAjaxController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormSelectTreeAjaxControllerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function fetchDataActionThrowsExceptionIfTcaOfTableDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1479386729);
        (new FormSelectTreeAjaxController(new FormDataCompiler(), $this->get(FlexFormTools::class), $this->get(TcaSchemaFactory::class)))->fetchDataAction(new ServerRequest());
    }

    #[Test]
    public function fetchDataActionThrowsExceptionIfTcaOfTableFieldDoesNotExist(): void
    {
        $serverRequest = (new ServerRequest())->withQueryParams([
            'tableName' => 'aTable',
            'fieldName' => 'aField',
        ]);
        $GLOBALS['TCA']['aTable']['columns'] = [];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1479386990);
        (new FormSelectTreeAjaxController(new FormDataCompiler(), $this->get(FlexFormTools::class), $this->get(TcaSchemaFactory::class)))->fetchDataAction($serverRequest);
    }
}
