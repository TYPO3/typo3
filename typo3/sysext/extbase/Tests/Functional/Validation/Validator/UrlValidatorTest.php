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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation\Validator;

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class UrlValidatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    public function urlDataProvider(): array
    {
        return [
            'Regular URL' => [
                'value' => 'https://typo3.org/',
                'isValid' => true,
            ],
            'Regular URL with subdomain' => [
                'value' => 'https://testify.typo3.org/',
                'isValid' => true,
            ],
            'Valid URL with trailing slash and path segment' => [
                'value' => 'https://testify.typo3.org/testify/',
                'isValid' => true,
            ],
            'Valid URL without trailing slash and path segment' => [
                'value' => 'https://testify.typo3.org/testify',
                'isValid' => true,
            ],
            'mailto' => [
                'value' => 'mailto:foobar@example.com',
                'isValid' => true,
            ],
            'mailto with subject' => [
                'value' => 'mailto:foobar@example.com?subject=Unit+test+results',
                'isValid' => true,
            ],
            'ftp' => [
                'value' => 'ftp://remotestorage.org',
                'isValid' => true,
            ],
            'tel' => [
                'value' => 'tel:01189998819991197253',
                'isValid' => true,
            ],
            'Some scheme that most likely does not exist' => [
                'value' => 'monk://convert.wololo',
                'isValid' => true,
            ],
            'Umlauts in domain' => [
                'value' => 'https://bürgerkarte.at',
                'isValid' => true,
            ],
            'Domain without protocol' => [
                'value' => 'typo3.org',
                'isValid' => false,
            ],
            'Empty value' => [
                'value' => '',
                'isValid' => true,
            ],
            'Null value' => [
                'value' => null,
                'isValid' => true,
            ],
            'Invalid value is only a string' => [
                'value' => 'testify',
                'isValid' => false,
            ],
            'Invalid value is integer' => [
                'value' => 1,
                'isValid' => false,
            ],
            'Invalid value is object' => [
                'value' => new \stdClass(),
                'isValid' => false,
            ],
            'Invalid value is closure' => [
                'value' => static function () {
                },
                'isValid' => false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider urlDataProvider
     */
    public function urlValidatorDetectsUrlsCorrectly($value, $expected): void
    {
        $validator = new UrlValidator();
        $validator->setOptions([]);
        self::assertSame($expected, !$validator->validate($value)->hasErrors());
    }
}
