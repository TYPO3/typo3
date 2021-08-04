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
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
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
            $this->getFeatureToggleFunction(),
            $this->getTraverseArrayFunction(),
        ];
    }

    protected function getIpFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'ip',
            static function () {
                // Not implemented, we only use the evaluator
            },
            static function ($arguments, $str) {
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
            static function () {
                // Not implemented, we only use the evaluator
            },
            static function ($arguments, $str) {
                return VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >=
                   VersionNumberUtility::convertVersionNumberToInteger($str);
            }
        );
    }

    protected function getLikeFunction(): ExpressionFunction
    {
        return new ExpressionFunction('like', static function () {
            // Not implemented, we only use the evaluator
        }, static function ($arguments, $haystack, $needle) {
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
        return new ExpressionFunction('date', static function () {
            // Not implemented, we only use the evaluator
        }, static function ($arguments, $format) {
            return GeneralUtility::makeInstance(Context::class)
                ->getAspect('date')->getDateTime()->format($format);
        });
    }

    protected function getFeatureToggleFunction(): ExpressionFunction
    {
        return new ExpressionFunction('feature', static function () {
            // Not implemented, we only use the evaluator
        }, static function ($arguments, $featureName) {
            return GeneralUtility::makeInstance(Features::class)
                ->isFeatureEnabled($featureName);
        });
    }

    public function getTraverseArrayFunction(): ExpressionFunction
    {
        return new ExpressionFunction('traverse', static function () {
            // Not implemented, we only use the evaluator
        }, static function ($arguments, $array, $path) {
            if (!is_array($array) || !is_string($path) || $path === '') {
                return '';
            }
            try {
                return ArrayUtility::getValueByPath($array, $path);
            } catch (MissingArrayPathException $e) {
                return '';
            }
        });
    }
}
