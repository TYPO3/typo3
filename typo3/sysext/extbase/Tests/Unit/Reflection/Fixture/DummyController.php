<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

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

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Fixture class with @validate annotations
 */
class DummyController extends ActionController
{
    /**
     * @param $fooParam
     */
    public function methodWithoutValidateAnnotationsAction($fooParam)
    {
    }

    /**
     * @param string $fooParam
     * @validate $fooParam StringLength (minimum=1,maximum=10)
     * @validate $fooParam NotEmpty
     * @validate $fooParam TYPO3.CMS.Extbase:NotEmpty
     * @validate $fooParam TYPO3.CMS.Extbase.Tests.Unit.Reflection.Fixture:DummyValidator
     * @validate $fooParam \TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator
     * @validate $fooParam TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator
     */
    public function methodWithValidateAnnotationsAction($fooParam)
    {
    }
}
