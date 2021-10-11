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

namespace TYPO3\CMS\Fluid\ViewHelpers\Sanitize;

use TYPO3\CMS\Core\Html\SanitizerBuilderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\HtmlSanitizer\Builder\BuilderInterface;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Passes a given content through `typo3/html-sanitizer` to mitigate potential
 * cross-site scripting occurrences. Given `default` build corresponds to class
 * `TYPO3\CMS\Core\Html\DefaultSanitizerBuilder` declaring allowed HTML tags,
 * attributes and their values.
 *
 * Examples
 * ========
 *
 * Default parameters
 * ------------------
 *
 * ::
 *
 *    <f:sanitize.html>
 *      <img src="/img.png" class="image" onmouseover="alert(document.location)">
 *    </f:sanitize.html>
 *
 * Output::
 *
 *    <img src="/img.png" class="image">
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {richTextFieldContent -> f:sanitize.html(build: 'default')}
 */
class HtmlViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @throws Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument(
            'build',
            'string',
            'preset name or class-like name of sanitization builder',
            false,
            'default'
        );
    }

    /**
     * @param array{build: string|class-string} $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string the parsed string.
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $renderChildrenClosure();
        $build = $arguments['build'] ?? 'default';
        return static::createSanitizer($build)->sanitize((string)$value);
    }

    protected static function createSanitizer(string $build): Sanitizer
    {
        if (class_exists($build) && is_a($build, BuilderInterface::class, true)) {
            $builder = GeneralUtility::makeInstance($build);
        } else {
            $factory = GeneralUtility::makeInstance(SanitizerBuilderFactory::class);
            $builder = $factory->build($build);
        }
        return $builder->build();
    }
}
