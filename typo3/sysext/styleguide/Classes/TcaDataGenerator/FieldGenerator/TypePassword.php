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

use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for hashed type=password fields
 *
 * @internal
 */
final class TypePassword extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'password',
            ],
        ],
    ];

    public function __construct(
        private readonly KauderwelschService $kauderwelschService,
        private readonly PasswordHashFactory $passwordHashFactory,
    ) {}

    public function generate(array $data): string
    {
        $defaultHashInstance = $this->passwordHashFactory->getDefaultHashInstance('BE');
        $plainText = $this->kauderwelschService->getPassword();
        if (array_key_exists('default', $data['fieldConfig']['config'])) {
            $plainText = $data['fieldConfig']['config']['default'];
        }
        return $defaultHashInstance->getHashedPassword($plainText);
    }
}
