<?php
namespace TYPO3\CMS\Form\Hooks;

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
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Scope: frontend
 * @internal
 */
class FormElementsOnSubmitHooks
{

    /**
     * This hook is invoked by the FormRuntime whenever values are mapped and validated
     * (after a form page was submitted)
     *
     * @param FormRuntime $formRuntime
     * @param RenderableInterface $renderable
     * @param mixed $elementValue submitted value of the element *before post processing*
     * @param array $requestArguments submitted raw request values
     * @see FormRuntime::mapAndValidate()
     * @internal
     */
    public function afterSubmit(FormRuntime $formRuntime, RenderableInterface $renderable, $elementValue, array $requestArguments = [])
    {
        if ($renderable->getType() === 'AdvancedPassword') {
            if ($elementValue['password'] !== $elementValue['confirmation']) {
                $processingRule = $renderable->getRootForm()->getProcessingRule($renderable->getIdentifier());
                $processingRule->getProcessingMessages()->addError(
                    GeneralUtility::makeInstance(ObjectManager::class)
                        ->get(Error::class, 'Password doesn\'t match confirmation', 1334768052)
                );
            }
            $elementValue = $elementValue['password'];
        }

        return $elementValue;
    }
}
