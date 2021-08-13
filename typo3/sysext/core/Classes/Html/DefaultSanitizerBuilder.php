<?php

declare(strict_types = 1);

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Html;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Builder\CommonBuilder;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3\HtmlSanitizer\Visitor\CommonVisitor;

/**
 * Builder, creating a `Sanitizer` instance with "default"
 * behavior for tags, attributes and values.
 *
 * @internal
 */
class DefaultSanitizerBuilder extends CommonBuilder
{
    public function __construct()
    {
        parent::__construct();
        // + URL must be on local host, or is absolute URI path
        $isOnCurrentHost = new Behavior\ClosureAttrValue(
            function (string $value): bool {
                return GeneralUtility::isValidUrl($value) && GeneralUtility::isOnCurrentHost($value)
                    || GeneralUtility::isAbsPath($value) && GeneralUtility::isAllowedAbsPath($value); // @todo incorrect abs path!
            }
        );
        // + starting with `t3://`
        $isTypo3Uri = new Behavior\RegExpAttrValue('#^t3://#');
        // + TYPO3 spam protected email address using JavaScript
        $isSpamProtectedEmailUri = new Behavior\RegExpAttrValue('#^javascript:linkTo_UnCryptMailto#');

        // extends common attributes for TYPO3-specific URIs
        $this->srcAttr->addValues($isOnCurrentHost);
        $this->srcsetAttr->addValues($isOnCurrentHost);
        $this->hrefAttr->addValues($isOnCurrentHost, $isTypo3Uri, $isSpamProtectedEmailUri);

        // @todo `style` used in Introduction Package, inline CSS should be removed
        $this->globalAttrs[] = new Behavior\Attr('style');
    }

    public function build(): Sanitizer
    {
        $behavior = $this->createBehavior();
        $visitor = GeneralUtility::makeInstance(CommonVisitor::class, $behavior);
        return GeneralUtility::makeInstance(Sanitizer::class, $visitor);
    }

    protected function createBasicTags(): array
    {
        /** @var Behavior\Tag[] $tags */
        $tags = parent::createBasicTags();
        // `... onclick="openPic(...)"` used in ContentObjectRenderer and AbstractMenuContentObject
        // @todo get rid of `onclick` since it conflicts with Content-Security-Policy
        $tags['a']->addAttrs(
            (new Behavior\Attr('onclick'))
                ->addValues(new Behavior\RegExpAttrValue('#^openPic\(#'))
        );
        return $tags;
    }

    protected function createBehavior(): Behavior
    {
        return parent::createBehavior()
            ->withName('default');
    }
}
