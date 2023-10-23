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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
            $this->getLoginUserFunction(),
            $this->getTSFEFunction(),
            $this->getUsergroupFunction(),
            $this->getSessionFunction(),
            $this->getSiteFunction(),
            $this->getSiteLanguageFunction(),
        ];
    }

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    protected function getLoginUserFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'loginUser',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $str) {
                trigger_error(
                    'TypoScript condition function "loginUser()" has been deprecated with TYPO3 v12 and' .
                    ' will be removed in v13. Use "frontend.user" and "backend.user" variables instead.',
                    E_USER_DEPRECATED
                );
                $user = $arguments['frontend']->user ?? $arguments['backend']->user;
                if ($user->isLoggedIn) {
                    foreach (GeneralUtility::trimExplode(',', $str, true) as $test) {
                        if ($test === '*' || (string)$user->userId === (string)$test) {
                            return true;
                        }
                    }
                }
                return false;
            }
        );
    }

    protected function getTSFEFunction(): ExpressionFunction
    {
        // @todo: This should probably vanish mid-term: TSFE is shrinking and calling
        //        properties on this object is risky and becomes more and more problematic.
        return new ExpressionFunction(
            'getTSFE',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments) {
                if (($arguments['tsfe'] ?? null) instanceof TypoScriptFrontendController) {
                    return $arguments['tsfe'];
                }
                // If TSFE is not given as argument, return null.
                return null;
            }
        );
    }

    /**
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
     */
    protected function getUsergroupFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'usergroup',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $str) {
                trigger_error(
                    'TypoScript condition function "usergroup()" has been deprecated with TYPO3 v12 and' .
                    ' will be removed in v13. Use "frontend.user" and "backend.user" variables instead.',
                    E_USER_DEPRECATED
                );
                $user = $arguments['frontend']->user ?? $arguments['backend']->user;
                $groupList = $user->userGroupList ?? '';
                // '0,-1' is the default usergroups string when not logged in!
                if ($groupList !== '0,-1' && $groupList !== '') {
                    foreach (GeneralUtility::trimExplode(',', $str, true) as $test) {
                        if ($test === '*' || GeneralUtility::inList($groupList, $test)) {
                            return true;
                        }
                    }
                }
                return false;
            }
        );
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
                // @todo: Provide session data differently and refrain from using TSFE.
                $tsfe = $arguments['tsfe'] ?? null;
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
}
