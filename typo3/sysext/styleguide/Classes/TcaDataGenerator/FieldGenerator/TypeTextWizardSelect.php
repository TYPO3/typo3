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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for type=text fields
 *
 * @internal
 */
final class TypeTextWizardSelect extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'text',
                'valuePicker' => [],
            ],
        ],
    ];

    public function generate(array $data): string
    {
        // Get second value from "items" of wizard
        $values = $data['fieldConfig']['config']['valuePicker']['items'];
        array_shift($values);
        $keyValue = array_shift($values);
        return $keyValue['value'];
    }
}
