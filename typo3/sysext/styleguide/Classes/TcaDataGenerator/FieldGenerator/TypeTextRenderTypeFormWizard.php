<?php
declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

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

use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for type=text fields
 */
class TypeTextRenderTypeFormWizard extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=text
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'text',
                'renderType' => 'formwizard',
            ],
        ],
    ];

    /**
     * Returns the generated value to be inserted into DB for this field
     *
     * @param array $data
     * @return string
     */
    public function generate(array $data): string
    {
        $formDefinition = '
confirmation = 1
postProcessor {
    1 = mail
    1 {
        recipientEmail =
        senderEmail =
    }
}
10 = TEXTLINE
10 {
    name = textField
    type = text
    label {
        value = textFieldLabel
    }
}
20 = SUBMIT
20 {
    name = submitButton
    type = submit
    value = Submit form
}
';
        return $formDefinition;
    }
}
