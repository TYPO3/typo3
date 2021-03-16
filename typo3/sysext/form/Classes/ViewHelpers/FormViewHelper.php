<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\ViewHelpers;

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
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper as FluidFormViewHelper;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Custom form ViewHelper that renders the form state instead of referrer fields
 *
 * Scope: frontend
 */
class FormViewHelper extends FluidFormViewHelper
{

    /**
     * Renders hidden form fields for referrer information about
     * the current request.
     *
     * @return string Hidden fields with referrer information
     */
    protected function renderHiddenReferrerFields()
    {
        $formRuntime = $this->getFormRuntime();
        $prefix = $this->prefixFieldName($this->getFormObjectName());

        $markup = $this->createHiddenInputElement(
            $prefix . '[__state]',
            $this->hashService->appendHmac(
                base64_encode(serialize($formRuntime->getFormState()))
            )
        );

        // ONLY assign `__session` if form is performing (uncached)
        if ($formRuntime->canProcessFormSubmission() && $formRuntime->getFormSession() !== null) {
            $markup .= $this->createHiddenInputElement(
                $prefix . '[__session]',
                $formRuntime->getFormSession()->getAuthenticatedIdentifier()
            );
        }
        return $markup;
    }

    /**
     * @param string $name
     * @param string $value
     * @return string
     */
    protected function createHiddenInputElement(string $name, string $value): string
    {
        $tagBuilder = GeneralUtility::makeInstance(TagBuilder::class, 'input');
        $tagBuilder->addAttribute('type', 'hidden');
        $tagBuilder->addAttribute('name', $name);
        $tagBuilder->addAttribute('value', $value);
        return $tagBuilder->render();
    }

    /**
     * We do NOT return NULL as in this case, the Form ViewHelpers do not enter $objectAccessorMode.
     * However, we return the form identifier.
     *
     * @return string
     */
    protected function getFormObjectName()
    {
        return $this->getFormRuntime()->getFormDefinition()->getIdentifier();
    }

    protected function getFormRuntime(): FormRuntime
    {
        return $this->arguments['object'];
    }
}
