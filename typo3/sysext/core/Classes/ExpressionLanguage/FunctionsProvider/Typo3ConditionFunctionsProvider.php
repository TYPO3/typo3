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

namespace TYPO3\CMS\Core\ExpressionLanguage\FunctionsProvider;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Functions available in 'TypoScript' context. Note these rely on variables
 * being hand over, see IncludeTreeConditionMatcherVisitor for more details.
 *
 * @internal
 */
class Typo3ConditionFunctionsProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions(): array
    {
        return [
            $this->getSessionFunction(),
            $this->getSiteFunction(),
            $this->getSiteLanguageFunction(),
            $this->getLocaleFunction(),
        ];
    }

    protected function getSessionFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'session',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $str) {
                $retVal = null;
                $keyParts = explode('|', $str);
                $sessionKey = array_shift($keyParts);
                $frontendUser = $arguments['request']->getFrontendUser();
                if ($frontendUser) {
                    $retVal = $frontendUser->getSessionData($sessionKey);
                    foreach ($keyParts as $keyPart) {
                        if (is_object($retVal)) {
                            $retVal = $retVal->{$keyPart};
                        } elseif (is_array($retVal)) {
                            $retVal = $retVal[$keyPart];
                        } else {
                            break;
                        }
                    }
                }
                return $retVal;
            }
        );
    }

    protected function getSiteFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'site',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $str) {
                $site = $arguments['site'] ?? null;
                if ($site instanceof SiteInterface) {
                    $methodName = 'get' . ucfirst(trim($str));
                    if (method_exists($site, $methodName)) {
                        return $site->$methodName();
                    }
                }
                return null;
            }
        );
    }

    protected function getSiteLanguageFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'siteLanguage',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $str) {
                $siteLanguage = $arguments['siteLanguage'] ?? null;
                if ($siteLanguage instanceof SiteLanguage) {
                    $methodName = 'get' . ucfirst(trim($str));
                    if (method_exists($siteLanguage, $methodName)) {
                        return $siteLanguage->$methodName();
                    }
                }
                return null;
            }
        );
    }

    protected function getLocaleFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'locale',
            static fn() => null, // Not implemented, we only use the evaluator
            static function (array $arguments) {
                $siteLanguage = $arguments['siteLanguage'] ?? null;
                if ($siteLanguage instanceof SiteLanguage) {
                    return $siteLanguage->getLocale();
                }
                return null;
            }
        );
    }
}
