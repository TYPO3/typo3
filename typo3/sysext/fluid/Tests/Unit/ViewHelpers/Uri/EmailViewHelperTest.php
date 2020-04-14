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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Uri;

use TYPO3\CMS\Fluid\ViewHelpers\Uri\EmailViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Testcase for the email uri view helper
 */
class EmailViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Uri\EmailViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new EmailViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderReturnsFirstResultOfGetMailTo()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'email' => 'some@email.tld'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();

        self::assertEquals('mailto:some@email.tld', $actualResult);
    }
}
