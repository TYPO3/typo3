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

namespace TYPO3\CMS\Install\Tests\Functional\ViewHelpers\Format;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class PhpErrorCodeViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function errorCodesDataProvider(): array
    {
        return [
            [
                'errorCode' => E_ERROR,
                'expectedString' => 'E_ERROR',
            ],
            [
                'errorCode' => E_ALL,
                'expectedString' => 'E_ALL',
            ],
            [
                'errorCode' => E_ERROR ^ E_WARNING ^ E_PARSE,
                'expectedString' => 'E_ERROR | E_WARNING | E_PARSE',
            ],
            [
                'errorCode' => E_RECOVERABLE_ERROR ^ E_USER_DEPRECATED,
                'expectedString' => 'E_RECOVERABLE_ERROR | E_USER_DEPRECATED',
            ],
        ];
    }

    #[DataProvider('errorCodesDataProvider')]
    #[Test]
    public function renderPhpCodesCorrectly(int $errorCode, string $expectedString): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('install', 'TYPO3\\CMS\\Install\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<install:format.phpErrorCode phpErrorCode="' . $errorCode . '" />');
        self::assertSame($expectedString, new TemplateView($context)->render());
    }
}
