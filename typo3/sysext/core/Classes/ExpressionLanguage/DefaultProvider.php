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

namespace TYPO3\CMS\Core\ExpressionLanguage;

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\ExpressionLanguage\FunctionsProvider\DefaultFunctionsProvider;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Prepare a couple of default variables and register some
 * general functions that work with it.
 *
 * @internal
 */
class DefaultProvider extends AbstractProvider
{
    public function __construct(
        Typo3Version $typo3Version,
        Context $context,
        Features $features,
    ) {
        $typo3 = new \stdClass();
        $typo3->version = $typo3Version->getVersion();
        $typo3->branch = $typo3Version->getBranch();
        $typo3->devIpMask = trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] ?? '');
        $this->expressionLanguageVariables = [
            'applicationContext' => (string)Environment::getContext(),
            'typo3' => $typo3,
            'date' => $context->getAspect('date'),
            'features' => $features,
        ];
        $this->expressionLanguageProviders[] = DefaultFunctionsProvider::class;
    }
}
