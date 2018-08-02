<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\ExpressionLanguage;

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

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Class TypoScriptFrontendConditionFunctionsProvider
 * @internal
 */
class TypoScriptFrontendConditionFunctionsProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        $functions = [
            $this->getSessionFunction(),
            $this->getSiteFunction(),
            $this->getSiteLanguageFunction(),
        ];

        return $functions;
    }

    protected function getSessionFunction(): ExpressionFunction
    {
        return new ExpressionFunction('session', function ($str) {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $str) {
            $retVal = null;
            $keyParts = explode('|', $str);
            $sessionKey = array_shift($keyParts);
            $tsfe = $GLOBALS['TSFE'];
            if ($tsfe && is_object($tsfe->fe_user)) {
                $retVal = $tsfe->fe_user->getSessionData($sessionKey);
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
        });
    }

    protected function getSiteFunction(): ExpressionFunction
    {
        return new ExpressionFunction('site', function ($str) {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $str) {
            /** @var RequestWrapper $requestWrapper */
            $requestWrapper = $arguments['request'];
            $site = $requestWrapper->getSite();
            if ($site instanceof Site) {
                $methodName = 'get' . ucfirst(trim($str));
                if (method_exists($site, $methodName)) {
                    return $site->$methodName();
                }
            }
            return null;
        });
    }

    protected function getSiteLanguageFunction(): ExpressionFunction
    {
        return new ExpressionFunction('siteLanguage', function ($str) {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $str) {
            /** @var RequestWrapper $requestWrapper */
            $requestWrapper = $arguments['request'];
            $siteLanguage = $requestWrapper->getSiteLanguage();
            if ($siteLanguage instanceof SiteLanguage) {
                $methodName = 'get' . ucfirst(trim($str));
                if (method_exists($siteLanguage, $methodName)) {
                    return $siteLanguage->$methodName();
                }
            }
            return null;
        });
    }
}
