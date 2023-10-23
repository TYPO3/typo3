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
use TYPO3\CMS\Core\ExpressionLanguage\RequestWrapper;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Default expression language functions. This is currently paired with
 * DefaultProvider class, which provides appropriate variables that
 * can be injected.
 *
 * @internal
 */
class DefaultFunctionsProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions(): array
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
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $str) {
                if ($str === 'devIP') {
                    $str = $arguments['typo3']->devIpMask;
                }
                $request = $arguments['request'] ?? null;
                if (!$request instanceof RequestWrapper) {
                    // @deprecated: ip() without given request should stop working in v13. Throw an exception here.
                    trigger_error(
                        'Using expression language function "ip(' . $str . ')" in a context without request.' .
                        ' A typical usage is user TSconfig or page TSconfig which can not provide a request object' .
                        ' since especially the DataHandler can not provide one. The implementation uses a fallback' .
                        ' for now, but will stop working in v13.',
                        E_USER_DEPRECATED
                    );
                    return GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $str);
                }
                return GeneralUtility::cmpIP($request->getNormalizedParams()->getRemoteAddress(), $str);
            }
        );
    }

    protected function getCompatVersionFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'compatVersion',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, mixed $str) {
                return VersionNumberUtility::convertVersionNumberToInteger($arguments['typo3']->branch) >=
                   VersionNumberUtility::convertVersionNumberToInteger((string)$str);
            }
        );
    }

    protected function getLikeFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'like',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $haystack, $needle) {
                return StringUtility::searchStringWildcard((string)$haystack, (string)$needle);
            }
        );
    }

    protected function getEnvFunction(): ExpressionFunction
    {
        return ExpressionFunction::fromPhp('getenv');
    }

    protected function getDateFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'date',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $format) {
                return $arguments['date']->getDateTime()->format($format);
            }
        );
    }

    protected function getFeatureToggleFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'feature',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $featureName) {
                return $arguments['features']->isFeatureEnabled($featureName);
            }
        );
    }

    protected function getTraverseArrayFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'traverse',
            static fn() => null, // Not implemented, we only use the evaluator
            static function ($arguments, $array, $path) {
                if (!is_array($array) || !is_string($path) || $path === '') {
                    return '';
                }
                try {
                    return ArrayUtility::getValueByPath($array, $path);
                } catch (MissingArrayPathException) {
                    return '';
                }
            }
        );
    }
}
