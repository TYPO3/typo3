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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Uri;

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExternalViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    public function renderDataProvider(): array
    {
        return [
            'renderReturnsSpecifiedUri' => [
                '<f:uri.external uri="http://www.some-domain.tld" />',
                'http://www.some-domain.tld',
            ],
            'renderAddsSchemeIfUriDoesNotContainScheme' => [
                '<f:uri.external uri="www.some-domain.tld" />',
                'https://www.some-domain.tld',
            ],
            'renderAddsSpecifiedSchemeIfUriDoesNotContainScheme' => [
                '<f:uri.external uri="some-domain.tld" defaultScheme="ftp" />',
                'ftp://some-domain.tld',
            ],
            'renderDoesNotAddEmptyScheme' => [
                '<f:uri.external uri="www.some-domain.tld" defaultScheme="" />',
                'www.some-domain.tld',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource($template);
        self::assertEquals($expected, $view->render());
    }
}
