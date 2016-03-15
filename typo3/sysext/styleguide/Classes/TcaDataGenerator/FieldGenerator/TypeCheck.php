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
 * Generate data for type=check fields
 */
class TypeCheck extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=check
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'check',
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
        // Nothing checked by default
        $value = 0;
        // If there is more than one option, set the second one checked
        if (isset($data['fieldConfig']['config']['items'])
            && is_array($data['fieldConfig']['config']['items'])
            && count($data['fieldConfig']['config']['items']) > 1
        ) {
            $value = 2;
        }
        return (string)$value;
    }
}
