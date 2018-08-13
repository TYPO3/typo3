<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Condition;

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

use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Exception;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 *
 * @internal
 */
class ConditionProvider extends AbstractProvider
{
    /**
     * @param FormRuntime $formRuntime
     */
    public function __construct(FormRuntime $formRuntime)
    {
        $this->expressionLanguageVariables = $this->getInitialExpressionLanguageVariables($formRuntime);

        $conditionContextDefinition = $formRuntime->getFormDefinition()->getConditionContextDefinition();

        foreach ($conditionContextDefinition['expressionLanguageProvider'] ?? [] as $expressionLanguageProviderName => $expressionLanguageProviderDefinition) {
            if (!isset($expressionLanguageProviderDefinition['implementationClassName'])) {
                throw new Exception(sprintf('The "implementationClassName" was not set for expression language provider "%s".', $expressionLanguageProviderName), 1526695869);
            }
            $implementationClassName = $expressionLanguageProviderDefinition['implementationClassName'];

            /** @see https://symfony.com/doc/4.0/components/expression_language/extending.html#using-expression-providers */
            $this->expressionLanguageProviders[] = new $implementationClassName();
        }

        foreach ($conditionContextDefinition['expressionLanguageVariableProvider'] ?? [] as $expressionLanguageVariableProviderName => $expressionLanguageVariableProviderDefinition) {
            if (!isset($expressionLanguageVariableProviderDefinition['implementationClassName'])) {
                throw new Exception(sprintf('The "implementationClassName" was not set for expression language variable provider "%s".', $expressionLanguageVariableProviderName), 1526695870);
            }

            $implementationClassName = $expressionLanguageVariableProviderDefinition['implementationClassName'];
            $expressionLanguageVariableProvider = new $implementationClassName($formRuntime);
            if (!($expressionLanguageVariableProvider instanceof ExpressionLanguageVariableProviderInterface)) {
                throw new Exception(sprintf('The expression language provider "%s" must implement "%s".', $implementationClassName, ExpressionLanguageVariableProviderInterface::class), 1526695874);
            }
            /** @see https://symfony.com/doc/4.0/components/expression_language.html#passing-in-variables */
            $this->expressionLanguageVariables[$expressionLanguageVariableProvider->getVariableName()] = $expressionLanguageVariableProvider->getVariableValue();
        }
    }

    /**
     * @param FormRuntime $formRuntime
     * @return array
     */
    protected function getInitialExpressionLanguageVariables(FormRuntime $formRuntime): array
    {
        $formValues = array_replace_recursive($formRuntime->getFormState()->getFormValues(), $formRuntime->getRequest()->getArguments());
        $page = $formRuntime->getCurrentPage() ?? $formRuntime->getFormDefinition()->getPageByIndex(0);

        $finisherIdentifier = '';
        if ($formRuntime->getCurrentFinisher() !== null) {
            $finisherIdentifier = (new \ReflectionClass($formRuntime->getCurrentFinisher()))->getShortName();
            $finisherIdentifier = preg_replace('/Finisher$/', '', $finisherIdentifier);
        }

        $contentObjectData = [];
        if (
            TYPO3_MODE === 'FE'
            && $this->getTypoScriptFrontendController()->cObj instanceof ContentObjectRenderer
        ) {
            $contentObjectData = $this->getTypoScriptFrontendController()->cObj->data;
        }

        return [
            'formRuntime' => $formRuntime,
            // some shortcuts
            'formValues' => $formValues,
            'stepIdentifier' => $page->getIdentifier(),
            'stepType' => $page->getType(),
            'finisherIdentifier' => $finisherIdentifier,
            'siteLanguage' => $formRuntime->getCurrentSiteLanguage(),
            'applicationContext' => GeneralUtility::getApplicationContext()->__toString(),
            'contentObject' => $contentObjectData,
        ];
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
