<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Mvc\Web;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Enforces cHash argument if it is required for a given request
 */
class CacheHashEnforcer implements SingletonInterface
{
    /**
     * @var CacheHashCalculator
     */
    protected $cacheHashCalculator;

    /**
     * @var TypoScriptFrontendController
     */
    protected $typoScriptFrontendController;

    /**
     * CacheHashEnforcer constructor.
     *
     * @param CacheHashCalculator $cacheHashCalculator
     * @param TypoScriptFrontendController|null $typoScriptFrontendController
     */
    public function __construct(
        CacheHashCalculator $cacheHashCalculator,
        TypoScriptFrontendController $typoScriptFrontendController = null
    ) {
        $this->cacheHashCalculator = $cacheHashCalculator;
        $this->typoScriptFrontendController = $typoScriptFrontendController ?: $GLOBALS['TSFE'];
    }

    /**
     * Checks if cHash is required for the current request and calls
     * TypoScriptFrontendController::reqCHash() if so.
     * This call will trigger a PageNotFoundException if arguments are required and cHash is not present.
     *
     * @param Request $request
     * @param string $pluginNamespace
     */
    public function enforceForRequest(Request $request, string $pluginNamespace)
    {
        $arguments = $request->getArguments();
        if (is_array($arguments) && count($arguments) > 0) {
            $parameters = [$pluginNamespace => $arguments];
            $parameters['id'] = $this->typoScriptFrontendController->id;
            $relevantParameters = $this->cacheHashCalculator->getRelevantParameters(
                http_build_query($parameters, '', '&', PHP_QUERY_RFC3986)
            );
            if (count($relevantParameters) > 0) {
                $this->typoScriptFrontendController->reqCHash();
            }
        }
    }
}
