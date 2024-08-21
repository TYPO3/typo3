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

namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\Variables\ScopedVariableProvider;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Validation results ViewHelper
 *
 * Examples
 * ========
 *
 * Output error messages as a list::
 *
 *    <f:form.validationResults>
 *       <f:if condition="{validationResults.flattenedErrors}">
 *          <ul class="errors">
 *             <f:for each="{validationResults.flattenedErrors}" as="errors" key="propertyPath">
 *                <li>{propertyPath}
 *                   <ul>
 *                      <f:for each="{errors}" as="error">
 *                         <li>{error.code}: {error}</li>
 *                      </f:for>
 *                   </ul>
 *                </li>
 *             </f:for>
 *          </ul>
 *       </f:if>
 *    </f:form.validationResults>
 *
 * Output::
 *
 *    <ul class="errors">
 *       <li>1234567890: Validation errors for argument "newBlog"</li>
 *    </ul>
 *
 * Output error messages for a single property::
 *
 *    <f:form.validationResults for="someProperty">
 *       <f:if condition="{validationResults.flattenedErrors}">
 *          <ul class="errors">
 *             <f:for each="{validationResults.errors}" as="error">
 *                <li>{error.code}: {error}</li>
 *             </f:for>
 *          </ul>
 *       </f:if>
 *    </f:form.validationResults>
 *
 * Output::
 *
 *    <ul class="errors">
 *      <li>1234567890: Some error message</li>
 *    </ul>
 */
final class ValidationResultsViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('for', 'string', 'The name of the error name (e.g. argument name or property name). This can also be a property path (like blog.title), and will then only display the validation errors of that property.', false, '');
        $this->registerArgument('as', 'string', 'The name of the variable to store the current error', false, 'validationResults');
    }

    /**
     * @return mixed @todo: Really mixed here, not string?
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $for = $arguments['for'];
        $as = $arguments['as'];

        if (!$renderingContext->hasAttribute(ServerRequestInterface::class)
            || !$renderingContext->getAttribute(ServerRequestInterface::class) instanceof RequestInterface
        ) {
            throw new \RuntimeException('ValidationResultsViewHelper needs an extbase request to work.', 1724244193);
        }
        $extbaseRequestParameters = $renderingContext->getAttribute(ServerRequestInterface::class)->getAttribute('extbase');
        $validationResults = $extbaseRequestParameters->getOriginalRequestMappingResults();
        if ($validationResults !== null && $for !== '') {
            $validationResults = $validationResults->forProperty($for);
        }

        $variableProvider = new ScopedVariableProvider($renderingContext->getVariableProvider(), new StandardVariableProvider([$as => $validationResults]));
        $renderingContext->setVariableProvider($variableProvider);

        $output = $renderChildrenClosure();

        $renderingContext->setVariableProvider($variableProvider->getGlobalVariableProvider());

        return $output;
    }
}
