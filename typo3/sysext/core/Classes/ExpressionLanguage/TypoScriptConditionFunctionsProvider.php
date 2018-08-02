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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Class TypoScriptConditionProvider
 * @internal
 */
class TypoScriptConditionFunctionsProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        return [
            $this->getIpFunction(),
            $this->getCompatVersionFunction(),
            $this->getLoginUserFunction(),
            $this->getTSFEFunction(),
            $this->getUsergroupFunction(),
        ];
    }

    protected function getIpFunction(): ExpressionFunction
    {
        return new ExpressionFunction('ip', function ($str) {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $str) {
            if ($str === 'devIP') {
                $str = trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
            }
            return (bool)GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $str);
        });
    }

    protected function getCompatVersionFunction(): ExpressionFunction
    {
        return new ExpressionFunction('compatVersion', function ($str) {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $str) {
            return VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= VersionNumberUtility::convertVersionNumberToInteger($str);
        });
    }

    protected function getLoginUserFunction(): ExpressionFunction
    {
        return new ExpressionFunction('loginUser', function ($str) {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $str) {
            $user = $arguments['backend']->user ?? $arguments['frontend']->user;
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
        return new ExpressionFunction('getTSFE', function ($str) {
            // Not implemented, we only use the evaluator
        }, function ($arguments) {
            return $GLOBALS['TSFE'];
        });
    }

    protected function getUsergroupFunction(): ExpressionFunction
    {
        return new ExpressionFunction('usergroup', function ($str) {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $str) {
            $user = $arguments['backend']->user ?? $arguments['frontend']->user;
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
}
