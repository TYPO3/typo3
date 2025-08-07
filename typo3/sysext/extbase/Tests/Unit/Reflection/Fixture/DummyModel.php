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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

use TYPO3\CMS\Extbase\Attribute as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;

/**
 * Fixture model
 */
class DummyModel extends AbstractEntity
{
    protected $propertyWithoutValidateAttributes;

    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['minimum' => 1, 'maximum' => 10]])]
    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    #[Extbase\Validate(['validator' => '\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator'])]
    #[Extbase\Validate(['validator' => NotEmptyValidator::class])]
    protected $propertyWithValidateAttributes;

    #[Extbase\FileUpload([
        'validation' => [
            'required' => true,
            'maxFiles' => 1,
            'fileSize' => ['minimum' => '0K', 'maximum' => '2M'],
            'mimeType' => ['allowedMimeTypes' => ['image/jpeg', 'image/png']],
            'allowedMimeTypes' => ['image/png'],
        ],
        'uploadFolder' => '1:/user_upload/',
    ])]
    protected $propertyWithFileUploadAttribute;

    public function __construct(
        #[Extbase\FileUpload([
            'validation' => [
                'required' => true,
                'maxFiles' => 1,
                'fileSize' => ['minimum' => '0K', 'maximum' => '2M'],
                'mimeType' => ['allowedMimeTypes' => ['image/jpeg', 'image/png']],
                'allowedMimeTypes' => ['image/png'],
            ],
            'uploadFolder' => '1:/user_upload/',
        ])]
        #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['minimum' => 1, 'maximum' => 10]])]
        #[Extbase\Validate(['validator' => 'NotEmpty'])]
        #[Extbase\Validate(['validator' => '\TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator'])]
        #[Extbase\Validate(['validator' => NotEmptyValidator::class])]
        public readonly string $dummyPromotedProperty
    ) {}
}
