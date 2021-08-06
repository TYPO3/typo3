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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Form;

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PasswordViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    /**
     * @test
     */
    public function renderCorrectlySetsTagName(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.password />');
        self::assertSame('<input type="password" name="" value="" />', $view->render());
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTypeNameAndValueAttributes(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.password name="NameOfTextbox" value="Current value" />');
        self::assertSame('<input type="password" name="NameOfTextbox" value="Current value" />', $view->render());
    }

    /**
     * @test
     */
    public function renderCorrectlySetsAutocompleteTagAttribute(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:form.password name="myNewPassword" value="" autocomplete="new-password" />');
        self::assertSame('<input autocomplete="new-password" type="password" name="myNewPassword" value="" />', $view->render());
    }
}
