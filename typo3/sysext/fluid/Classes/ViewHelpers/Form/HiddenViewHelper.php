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

namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

/**
 * ViewHelper which renders an `<input type="hidden" ...>` tag.
 *
 * ```
 *   <f:form.hidden name="myHiddenValue" value="42" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-hidden
 */
final class HiddenViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'respectSubmittedDataValue',
            'bool',
            'enable or disable the usage of the submitted values',
            false,
            true
        );
    }

    public function render(): string
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $this->setRespectSubmittedDataValue($this->arguments['respectSubmittedDataValue']);

        $this->tag->addAttribute('type', 'hidden');
        $this->tag->addAttribute('name', $name);
        $this->tag->addAttribute('value', $this->getValueAttribute());

        $this->addAdditionalIdentityPropertiesIfNeeded();

        return $this->tag->render();
    }
}
