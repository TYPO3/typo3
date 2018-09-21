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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Class DefaultFunctionsProvider
 * @internal
 */
class DefaultFunctionsProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        return [
            $this->getIpFunction(),
            $this->getCompatVersionFunction(),
            $this->getLikeFunction(),
            $this->getEnvFunction(),
            $this->getDateFunction(),
        ];
    }

    protected function getIpFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'ip',
            function () {
                // Not implemented, we only use the evaluator
            },
            function ($arguments, $str) {
                if ($str === 'devIP') {
                    $str = trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] ?? '');
                }
                return (bool)GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $str);
            }
        );
    }

    protected function getCompatVersionFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'compatVersion',
            function () {
                // Not implemented, we only use the evaluator
            },
            function ($arguments, $str) {
                return VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >=
                   VersionNumberUtility::convertVersionNumberToInteger($str);
            }
        );
    }

    protected function getLikeFunction(): ExpressionFunction
    {
        return new ExpressionFunction('like', function () {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $haystack, $needle) {
            $result = StringUtility::searchStringWildcard((string)$haystack, (string)$needle);
            return $result;
        });
    }

    protected function getEnvFunction(): ExpressionFunction
    {
        return ExpressionFunction::fromPhp('getenv');
    }

    protected function getDateFunction(): ExpressionFunction
    {
        return new ExpressionFunction('date', function () {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $format) {
            return GeneralUtility::makeInstance(Context::class)
                ->getAspect('date')->getDateTime()->format($format);
        });
    }
}
