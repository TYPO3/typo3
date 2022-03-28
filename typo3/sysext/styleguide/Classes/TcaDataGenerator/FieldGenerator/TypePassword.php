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

use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for hashed type=password fields
 */
class TypePassword extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * General match if type=password
     *
     * @var array
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'password',
            ],
        ],
    ];

    /**
     * Returns the generated value to be inserted into DB for this field
     */
    public function generate(array $data): string
    {
        $saltFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
        $defaultHashInstance = $saltFactory->getDefaultHashInstance('BE');
        $plainText = $this->kauderwelschService->getPassword();
        if (array_key_exists('default', $data['fieldConfig']['config'])) {
            $plainText = $data['fieldConfig']['config']['default'];
        }
        return $defaultHashInstance->getHashedPassword($plainText);
    }
}
