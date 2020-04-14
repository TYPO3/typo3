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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Configuration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionConversionService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FormDefinitionConversionServiceTest extends UnitTestCase
{

    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function addHmacDataAddsHmacHashes()
    {
        $formDefinitionConversionService = $this->getAccessibleMock(
            FormDefinitionConversionService::class,
            [
                'generateSessionToken',
                'persistSessionToken',
            ],
            [],
            '',
            false
        );

        $sessionToken = '123';
        $formDefinitionConversionService->expects(self::any())->method(
            'generateSessionToken'
        )->willReturn($sessionToken);

        $formDefinitionConversionService->expects(self::any())->method(
            'persistSessionToken'
        )->willReturn(null);

        GeneralUtility::setSingletonInstance(FormDefinitionConversionService::class, $formDefinitionConversionService);

        $input = [
            'prototypeName' => 'standard',
            'identifier' => 'test',
            'type' => 'Form',
            'heinz' => 1,
            'klaus' => [],
            'klaus1' => [
                '_label' => 'x',
                '_value' => 'y',
            ],
            'sabine' => [
                'heinz' => '2',
                'klaus' => [],
                'horst' => [
                    'heinz' => '',
                    'paul' => [[]],
                ],
            ],
        ];

        $data = $formDefinitionConversionService->addHmacData($input);

        $expected = [
            'prototypeName' => 'standard',
            'identifier' => 'test',
            'type' => 'Form',
            'heinz' => 1,
            'klaus' => [],
            'klaus1' => [
                '_label' => 'x',
                '_value' => 'y',
            ],
            'sabine' => [
                'heinz' => '2',
                'klaus' => [],
                'horst' => [
                    'heinz' => '',
                    'paul' => [[]],
                     '_orig_heinz' => [
                         'value' => '',
                         'hmac' => $data['sabine']['horst']['_orig_heinz']['hmac'],
                     ],
                ],
                 '_orig_heinz' => [
                     'value' => '2',
                     'hmac' => $data['sabine']['_orig_heinz']['hmac'],
                 ],
            ],
             '_orig_prototypeName' => [
                 'value' => 'standard',
                 'hmac' => $data['_orig_prototypeName']['hmac'],
             ],
             '_orig_identifier' => [
                 'value' => 'test',
                 'hmac' => $data['_orig_identifier']['hmac'],
             ],
             '_orig_type' => [
                 'value' => 'Form',
                 'hmac' => $data['_orig_type']['hmac'],
             ],
             '_orig_heinz' => [
                 'value' => 1,
                 'hmac' => $data['_orig_heinz']['hmac'],
             ],
        ];

        self::assertSame($expected, $data);
    }

    /**
     * @test
     */
    public function removeHmacDataRemoveHmacs()
    {
        $formDefinitionConversionService = new FormDefinitionConversionService();
        GeneralUtility::setSingletonInstance(FormDefinitionConversionService::class, $formDefinitionConversionService);

        $input = [
            'prototypeName' => 'standard',
            'identifier' => 'test',
            'heinz' => 1,
            'klaus' => [],
            'klaus1' => [
                '_label' => 'x',
                '_value' => 'y',
            ],
            'sabine' => [
                'heinz' => '2',
                'klaus' => [],
                'horst' => [
                    'heinz' => '',
                    'paul' => [[]],
                     '_orig_heinz' => [
                         'value' => '',
                         'hmac' => '12345',
                     ],
                ],
                 '_orig_heinz' => [
                     'value' => '2',
                     'hmac' => '12345',
                 ],
            ],
             '_orig_prototypeName' => [
                 'value' => 'standard',
                 'hmac' => '12345',
             ],
             '_orig_identifier' => [
                 'value' => 'test',
                 'hmac' => '12345',
             ],
             '_orig_heinz' => [
                 'value' => 1,
                 'hmac' => '12345',
             ],
        ];

        $expected = [
            'prototypeName' => 'standard',
            'identifier' => 'test',
            'heinz' => 1,
            'klaus' => [],
            'klaus1' => [
                '_label' => 'x',
                '_value' => 'y',
            ],
            'sabine' => [
                'heinz' => '2',
                'klaus' => [],
                'horst' => [
                    'heinz' => '',
                    'paul' => [[]],
                ],
            ],
        ];

        self::assertSame($expected, $formDefinitionConversionService->removeHmacData($input));
    }
}
