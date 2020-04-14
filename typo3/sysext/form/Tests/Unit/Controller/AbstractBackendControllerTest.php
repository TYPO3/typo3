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

namespace TYPO3\CMS\Form\Tests\Unit\Controller;

use TYPO3\CMS\Form\Controller\AbstractBackendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractBackendControllerTest extends UnitTestCase
{

    /**
     * @test
     */
    public function resolveResourcePathsExpectResolve()
    {
        $mockController = $this->getAccessibleMockForAbstractClass(
            AbstractBackendController::class,
            [],
            '',
            false
        );

        $input = [
            0 => 'EXT:form/Resources/Public/Css/form.css'
        ];

        $expected = [
            0 => 'typo3/sysext/form/Resources/Public/Css/form.css'
        ];

        self::assertSame($expected, $mockController->_call('resolveResourcePaths', $input));
    }
}
