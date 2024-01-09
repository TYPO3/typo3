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

namespace TYPO3\CMS\Core\Tests\Functional\ExpressionLanguage;

use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ResolverTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function evaluateWithTyposcriptAndTsfeEvaluatesCorrectly(): void
    {
        // @todo: This will raise a deprecation log level entry later, when TSFE->id is
        //        actively deprecated. See hint in Typo3ConditionFunctionsProvider.
        $frontendControllerMock = $this
                ->getMockBuilder(TypoScriptFrontendController::class)
                ->disableOriginalConstructor()
                ->getMock();
        $frontendControllerMock->id = 123;

        $resolver = new Resolver('typoscript', ['tsfe' => $frontendControllerMock]);
        self::assertFalse($resolver->evaluate('getTSFE() && getTSFE().id == 321'));
        self::assertTrue($resolver->evaluate('getTSFE() && getTSFE().id == 123'));
        self::assertFalse($resolver->evaluate('getTSFE()?.id == 321'));
        self::assertTrue($resolver->evaluate('getTSFE()?.id == 123'));
    }

    /**
     * @test
     */
    public function evaluateWithTyposcriptWithoutTsfeEvaluatesCorrectly(): void
    {
        $resolver = new Resolver('typoscript', []);
        self::assertFalse($resolver->evaluate('getTSFE() && getTSFE().id == 123'));
        self::assertFalse($resolver->evaluate('getTSFE()?.id == 123'));
    }
}
