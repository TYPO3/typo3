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
use TYPO3\CMS\Core\Html\SanitizerInitiator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\HtmlSanitizer\Builder\BuilderInterface;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to pass a given content through `typo3/html-sanitizer` to mitigate potential
 * cross-site scripting occurrences. The `build` option by default uses the class
 * `TYPO3\CMS\Core\Html\DefaultSanitizerBuilder`, which declares allowed HTML tags,
 * attributes and their values.
 *
 * ```
 *   <f:sanitize.html>
 *       <img src="/img.png" class="image" onmouseover="alert(document.location)">
 *   </f:sanitize.html>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-sanitize-html
 * @see \TYPO3\CMS\Core\Html\DefaultSanitizerBuilder
 */
final class HtmlViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('build', 'string', 'preset name or class-like name of sanitization builder', false, 'default');
    }

    public function render(): string
    {
        $value = $this->renderChildren();
        $build = $this->arguments['build'];
        return self::createSanitizer($build)->sanitize((string)$value, self::createInitiator());
    }

    private static function createInitiator(): SanitizerInitiator
    {
        return GeneralUtility::makeInstance(SanitizerInitiator::class, self::class);
    }

    private static function createSanitizer(string $build): Sanitizer
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
