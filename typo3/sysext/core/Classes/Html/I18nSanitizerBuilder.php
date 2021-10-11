<?php

declare(strict_types=1);

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
use TYPO3\HtmlSanitizer\Behavior\Attr\UriAttrValueBuilder;
use TYPO3\HtmlSanitizer\Builder\BuilderInterface;
use TYPO3\HtmlSanitizer\Sanitizer;
use TYPO3\HtmlSanitizer\Visitor\CommonVisitor;

/**
 * Builder, creating a `Sanitizer` instance for "i18n"
 * behavior for tags, attributes and values. Basically used
 * for language labels containing HTML.
 *
 * @internal
 */
class I18nSanitizerBuilder implements BuilderInterface
{
    public function build(): Sanitizer
    {
        $globalAttrs = $this->createGlobalAttrs();
        $httpUriBuilder = GeneralUtility::makeInstance(UriAttrValueBuilder::class)
            ->allowSchemes('http', 'https');

        $behavior = GeneralUtility::makeInstance(Behavior::class)
            ->withTags(
                (new Behavior\Tag('a', Behavior\Tag::ALLOW_CHILDREN))
                    ->addAttrs(...$globalAttrs)
                    ->addAttrs(...$this->createAttrs('rel', 'target'))
                    ->addAttrs(
                        (new Behavior\Attr('href'))
                            ->withValues(...$httpUriBuilder->getValues()),
                    ),
                (new Behavior\Tag('b', Behavior\Tag::ALLOW_CHILDREN))
                    ->addAttrs(...$globalAttrs),
                (new Behavior\Tag('br'))
                    ->addAttrs(...$globalAttrs),
                (new Behavior\Tag('div', Behavior\Tag::ALLOW_CHILDREN))
                    ->addAttrs(...$globalAttrs),
                (new Behavior\Tag('em', Behavior\Tag::ALLOW_CHILDREN))
                    ->addAttrs(...$globalAttrs),
                (new Behavior\Tag('i', Behavior\Tag::ALLOW_CHILDREN))
                    ->addAttrs(...$globalAttrs),
                (new Behavior\Tag('li', Behavior\Tag::ALLOW_CHILDREN))
                    ->addAttrs(...$globalAttrs),
                (new Behavior\Tag('span', Behavior\Tag::ALLOW_CHILDREN))
                    ->addAttrs(...$globalAttrs),
                (new Behavior\Tag('strong', Behavior\Tag::ALLOW_CHILDREN))
                    ->addAttrs(...$globalAttrs),
                (new Behavior\Tag('ul', Behavior\Tag::ALLOW_CHILDREN))
                    ->addAttrs(...$globalAttrs)
            );

        $visitor = GeneralUtility::makeInstance(CommonVisitor::class, $behavior);
        return GeneralUtility::makeInstance(Sanitizer::class, $visitor);
    }

    /**
     * @return Behavior\Attr[]
     */
    protected function createGlobalAttrs(): array
    {
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes
        $attrs = $this->createAttrs(
            'class',
            'role',
            'tabindex',
            'title',
        );
        $attrs[] = new Behavior\Attr('aria-', Behavior\Attr::NAME_PREFIX);
        $attrs[] = new Behavior\Attr('data-', Behavior\Attr::NAME_PREFIX);
        return $attrs;
    }

    /**
     * @param string ...$names
     * @return Behavior\Attr[]
     */
    protected function createAttrs(string ...$names): array
    {
        return array_map(
            function (string $name) {
                return new Behavior\Attr($name);
            },
            $names
        );
    }
}
