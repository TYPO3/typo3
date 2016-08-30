<?php
namespace TYPO3\CMS\Form\Domain\Validator;

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

class RequiredValidator extends AbstractValidator
{
    /**
     * Constant for localisation
     *
     * @var string
     */
    const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_required';

    /**
     * @var bool
     */
    protected $allFieldsAreEmpty = true;

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param mixed $value
     * @return void
     */
    public function isValid($value)
    {
        if (is_array($value)) {
            array_walk_recursive($value, function ($value, $key, $validator) {
                if (!empty($value) || $value === '0' || $value === 0) {
                    $validator->setAllFieldsAreEmpty(false);
                }
            },
                $this
            );
            if ($this->getAllFieldsAreEmpty()) {
                $this->addError(
                    $this->renderMessage(
                        $this->options['errorMessage'][0],
                        $this->options['errorMessage'][1],
                        'error'
                    ),
                    1441980673
                );
            }
        } else {
            if (
                empty($value)
                && $value !== 0
                && $value !== '0'
            ) {
                $this->addError(
                    $this->renderMessage(
                        $this->options['errorMessage'][0],
                        $this->options['errorMessage'][1],
                        'error'
                    ),
                    144198067
                );
            }
        }
    }

    /**
     * A helper method for the array_walk_recursive callback in the
     * function isValid().
     * If the callback detect a empty value, the
     * property allFieldsAreEmpty is set to TRUE.
     *
     * @param bool $allFieldsAreEmpty
     * @return void
     */
    protected function setAllFieldsAreEmpty($allFieldsAreEmpty = true)
    {
        $this->allFieldsAreEmpty = $allFieldsAreEmpty;
    }

    /**
     * A helper method for the array_walk_recursive callback in the
     * function isValid().
     * If the callback detect a empty value, the
     * property allFieldsAreEmpty is set to TRUE.
     *
     * @return bool
     */
    protected function getAllFieldsAreEmpty()
    {
        return $this->allFieldsAreEmpty;
    }
}
