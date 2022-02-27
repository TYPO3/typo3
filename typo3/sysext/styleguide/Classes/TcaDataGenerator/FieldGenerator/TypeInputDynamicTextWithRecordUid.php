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
 * Generate data for type=input fields
 * "lipsum 23" for some special children
 */
class TypeInputDynamicTextWithRecordUid extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=input
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'input',
            ],
        ],
    ];

    /**
     * Some inline scenarios need multiple children table rows. To distinct those rows from each
     * other, the uid of the row is added.
     *
     * This match() is hardcoded for some specific child tables only.
     *
     * @param array $data
     * @return bool
     */
    public function match(array $data): bool
    {
        $match = parent::match($data);
        if ($match) {
            if ($data['tableName'] !== 'tx_styleguide_inline_expandsingle_child'
                && $data['tableName'] !== 'tx_styleguide_inline_usecombination_child'
                && $data['tableName'] !== 'tx_styleguide_inline_usecombinationbox_child'
                && $data['tableName'] !== 'tx_styleguide_inline_mnsymmetric'
                && $data['tableName'] !== 'tx_styleguide_inline_mnsymmetricgroup'
                && $data['tableName'] !== 'tx_styleguide_inline_mn_child'
            ) {
                $match = false;
            }
        }
        return $match;
    }

    /**
     * To determine different children in an easy way, this input field
     * generates a string combined with the record uid
     *
     * @param array $data
     * @return string
     */
    public function generate(array $data): string
    {
        return (string)($this->kauderwelschService->getWord() . $data['fieldValues']['uid']);
    }
}
