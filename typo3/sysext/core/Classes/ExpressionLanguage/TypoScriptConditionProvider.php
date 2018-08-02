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

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TypoScriptConditionProvider
 * @internal
 */
class TypoScriptConditionProvider extends AbstractProvider
{
    public function __construct(array $expressionLanguageVariables = [], array $expressionLanguageProviders = [])
    {
        $typo3 = new \stdClass();
        $typo3->version = TYPO3_version;
        $typo3->branch = TYPO3_branch;
        $typo3->devIpMask = trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
        $this->expressionLanguageVariables = array_merge([
            'request' => GeneralUtility::makeInstance(RequestWrapper::class),
            'applicationContext' => (string)GeneralUtility::getApplicationContext(),
            'typo3' => $typo3,
        ], $expressionLanguageVariables);

        $this->expressionLanguageProviders = $expressionLanguageProviders;
        $this->initFunctions();
    }

    protected function initFunctions(): void
    {
        $this->expressionLanguageProviders[] = GeneralUtility::makeInstance(DefaultFunctionsProvider::class);
        $this->expressionLanguageProviders[] = GeneralUtility::makeInstance(TypoScriptConditionFunctionsProvider::class);

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][__CLASS__]['additionalExpressionLanguageProvider'] ?? [] as $className) {
            $expressionLanguageProvider = GeneralUtility::makeInstance($className);
            if ($expressionLanguageProvider instanceof ExpressionFunctionProviderInterface) {
                $this->expressionLanguageProviders[] = $expressionLanguageProvider;
            }
        }
    }
}
