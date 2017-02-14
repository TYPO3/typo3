<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Model\FormElements;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * A password with confirmation form element
 *
 * Scope: frontend
 * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
 */
class AdvancedPassword extends AbstractFormElement
{

    /**
     * This callback is invoked by the FormRuntime whenever values are mapped and validated
     * (after a form page was submitted)
     *
     * @param FormRuntime $formRuntime
     * @param mixed $elementValue submitted value of the element *before post processing*
     * @param array $requestArguments submitted raw request values
     * @see FormRuntime::mapAndValidate()
     * @internal
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function onSubmit(FormRuntime $formRuntime, &$elementValue, array $requestArguments = [])
    {
        GeneralUtility::logDeprecatedFunction();
    }
}
