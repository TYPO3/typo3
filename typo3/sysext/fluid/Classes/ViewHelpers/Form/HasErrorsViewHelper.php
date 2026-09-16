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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * ViewHelper which checks whether a property has validation errors, implemented as a
 * condition like an "if" construct. Errors of sub properties count as well, so "author"
 * is true if only "author.email" has an error.
 *
 * ```
 *   <div class="{f:form.hasErrors(for: 'blog.title', then: 'has-error')}">
 *     <f:form.textfield property="title" />
 *   </div>
 *
 *   <f:form.hasErrors for="blog.title">
 *     <f:then>
 *        The title has at least one validation error.
 *     </f:then>
 *     <f:else>
 *        The title is fine.
 *     </f:else>
 *   </f:form.hasErrors>
 * ```
 *
 * Use `<f:form.validationResults>` instead to access the error objects themselves.
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-haserrors
 */
final class HasErrorsViewHelper extends AbstractConditionViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('for', 'string', 'The property path to check, for example "blog.title".', true);
    }

    public static function verdict(array $arguments, RenderingContextInterface $renderingContext): bool
    {
        $for = $arguments['for'] ?? null;
        if (!is_string($for) || $for === '') {
            // A property path that does not resolve would silently match the whole form, which
            // is never what the template meant when it named a property.
            throw new \RuntimeException(
                'The "for" argument of the f:form.hasErrors ViewHelper must be a non-empty property path.',
                1789587437
            );
        }
        if (!$renderingContext->hasAttribute(ServerRequestInterface::class)
            || !$renderingContext->getAttribute(ServerRequestInterface::class) instanceof RequestInterface
        ) {
            throw new \RuntimeException('HasErrorsViewHelper needs an extbase request to work.', 1789587438);
        }
        $extbaseRequestParameters = $renderingContext->getAttribute(ServerRequestInterface::class)->getAttribute('extbase');
        return $extbaseRequestParameters->getOriginalRequestMappingResults()->forProperty($for)->hasErrors();
    }
}
