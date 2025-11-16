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
 * ViewHelper which renders a simple password text box `<input type="password">`.
 *
 * ```
 *   <f:form.password name="myPassword" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-password
 */
final class PasswordViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
        $this->registerArgument(
            'respectSubmittedDataValue',
            'bool',
            'If set to false (default), any user-submitted data is not displayed in the output. If set to true, the password is emitted as clear text in the response. This is not recommended from a security point of view.',
            false,
            false
        );
    }

    public function render(): string
    {
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);
        $this->setRespectSubmittedDataValue($this->arguments['respectSubmittedDataValue']);

        $this->tag->addAttribute('type', 'password');
        $this->tag->addAttribute('name', $name);
        $this->tag->addAttribute('value', (string)$this->getValueAttribute());

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();

        return $this->tag->render();
    }
}
