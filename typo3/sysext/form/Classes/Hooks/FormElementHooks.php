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

namespace TYPO3\CMS\Form\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * Scope: frontend
 * @internal
 */
class FormElementHooks
{

    /**
     * This hook is invoked by the FormRuntime for each form element
     * **after** a form page was submitted but **before** values are
     * property-mapped, validated and pushed within the FormRuntime's `FormState`.
     *
     * @param FormRuntime $formRuntime
     * @param RenderableInterface $renderable
     * @param mixed $elementValue submitted value of the element *before post processing*
     * @param array $requestArguments submitted raw request values
     * @return mixed
     * @see FormRuntime::mapAndValidate()
     * @internal
     */
    public function afterSubmit(FormRuntime $formRuntime, RenderableInterface $renderable, $elementValue, array $requestArguments = [])
    {
        if ($renderable->getType() === 'AdvancedPassword') {
            if ($elementValue['password'] !== $elementValue['confirmation']) {
                $processingRule = $renderable->getRootForm()->getProcessingRule($renderable->getIdentifier());
                $processingRule->getProcessingMessages()->addError(
                    GeneralUtility::makeInstance(
                        Error::class,
                        GeneralUtility::makeInstance(TranslationService::class)->translate('validation.error.1556283177', null, 'EXT:form/Resources/Private/Language/locallang.xlf'),
                        1556283177
                    )
                );
            }
            $elementValue = $elementValue['password'];
        }

        return $elementValue;
    }

    /**
     * This is a hook that is invoked by the rendering system **before**
     * the corresponding element is rendered.
     *
     * @param FormRuntime $formRuntime
     * @param RootRenderableInterface $renderable
     */
    public function beforeRendering(FormRuntime $formRuntime, RootRenderableInterface $renderable)
    {
        if ($renderable->getType() === 'Date') {
            $date = $formRuntime[$renderable->getIdentifier()];
            if ($date instanceof \DateTime) {
                // @see https://www.w3.org/TR/2011/WD-html-markup-20110405/input.date.html#input.date.attrs.value
                // 'Y-m-d' = https://tools.ietf.org/html/rfc3339#section-5.6 -> full-date
                $formRuntime[$renderable->getIdentifier()] = $date->format('Y-m-d');
            }
        }
    }
}
