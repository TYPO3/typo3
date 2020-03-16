<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\ExpressionLanguage\FunctionsProvider;

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
use TYPO3\CMS\Core\Exception\MissingTsfeException;
use TYPO3\CMS\Core\ExpressionLanguage\RequestWrapper;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class TypoScriptConditionProvider
 * @internal
 */
class Typo3ConditionFunctionsProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
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

    protected function getLoginUserFunction(): ExpressionFunction
    {
        return new ExpressionFunction('loginUser', function () {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $str) {
            $user = $arguments['frontend']->user ?? $arguments['backend']->user;
            if ($user->isLoggedIn) {
                foreach (GeneralUtility::trimExplode(',', $str, true) as $test) {
                    if ($test === '*' || (string)$user->userId === (string)$test) {
                        return true;
                    }
                }
            }
            return false;
        });
    }

    protected function getTSFEFunction(): ExpressionFunction
    {
        return new ExpressionFunction('getTSFE', function () {
            // Not implemented, we only use the evaluator
        }, function ($arguments) {
            if (($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController) {
                return $GLOBALS['TSFE'];
            }
            throw new MissingTsfeException('TSFE is not available in this context', 1578831632);
        });
    }

    protected function getUsergroupFunction(): ExpressionFunction
    {
        return new ExpressionFunction('usergroup', function () {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $str) {
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
        });
    }

    protected function getSessionFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'session',
            function () {
                // Not implemented, we only use the evaluator
            },
            function ($arguments, $str) {
                $retVal = null;
                $keyParts = explode('|', $str);
                $sessionKey = array_shift($keyParts);
                // @todo fetch data from be session if available
                $tsfe = $GLOBALS['TSFE'] ?? null;
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
            function () {
                // Not implemented, we only use the evaluator
            },
            function ($arguments, $str) {
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
            }
        );
    }

    protected function getSiteLanguageFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'siteLanguage',
            function () {
                // Not implemented, we only use the evaluator
            },
            function ($arguments, $str) {
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
            }
        );
    }
}
