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

namespace TYPO3\CMS\Core\Html;

use TYPO3\CMS\Core\Html\Visitor\UnwrapTagVisitor;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\HtmlSanitizer\Builder\CommonBuilder;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3\HtmlSanitizer\Visitor\CommonVisitor;

/**
 * Builder, creating a `Sanitizer` instance for previews in backend, skipping any links
 *
 * @internal
 */
class PreviewSanitizerBuilder extends CommonBuilder implements SingletonInterface
{
    public function build(): Sanitizer
    {
        $behavior = $this->createBehavior();
        $visitor = GeneralUtility::makeInstance(CommonVisitor::class, $behavior);
        $unwrapTagVisitor = GeneralUtility::makeInstance(UnwrapTagVisitor::class);
        return GeneralUtility::makeInstance(Sanitizer::class, $behavior, $visitor, $unwrapTagVisitor);
    }
}
