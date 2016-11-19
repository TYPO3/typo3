<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Model\FormElements;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * A password with confirmation form element
 *
 * Scope: frontend
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
     * @return void
     * @see FormRuntime::mapAndValidate()
     * @internal
     */
    public function onSubmit(FormRuntime $formRuntime, &$elementValue, array $requestArguments = [])
    {
        if ($elementValue['password'] !== $elementValue['confirmation']) {
            $processingRule = $this->getRootForm()->getProcessingRule($this->getIdentifier());
            $processingRule->getProcessingMessages()->addError(
                GeneralUtility::makeInstance(ObjectManager::class)
                    ->get(Error::class, 'Password doesn\'t match confirmation', 1334768052)
            );
        }
        $elementValue = $elementValue['password'];
    }
}
