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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
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
class DefaultSanitizerBuilder extends CommonBuilder implements SingletonInterface
{
    private Behavior $behavior;

    public function __construct()
    {
        parent::__construct();
        // + URL must be on local host, or is absolute URI path
        $isOnCurrentHost = new Behavior\ClosureAttrValue(
            static function (string $value): bool {
                return GeneralUtility::isValidUrl($value) && GeneralUtility::isOnCurrentHost($value)
                    || PathUtility::isAbsolutePath($value) && GeneralUtility::isAllowedAbsPath($value); // @todo incorrect abs path!
            }
        );
        // + starting with `t3://`
        $isTypo3Uri = new Behavior\RegExpAttrValue('#^t3://#');

        // extends common attributes for TYPO3-specific URIs
        $this->srcAttr->addValues($isOnCurrentHost);
        $this->srcsetAttr->addValues($isOnCurrentHost);
        $this->hrefAttr->addValues($isOnCurrentHost, $isTypo3Uri);

        // @todo `style` used in Introduction Package, inline CSS should be removed
        $this->globalAttrs[] = new Behavior\Attr('style');
    }

    public function build(): Sanitizer
    {
        $behavior = $this->createBehavior();
        $visitor = GeneralUtility::makeInstance(CommonVisitor::class, $behavior);
        return GeneralUtility::makeInstance(Sanitizer::class, $behavior, $visitor);
    }

    protected function createBehavior(): Behavior
    {
        if (!isset($this->behavior)) {
            $this->behavior = parent::createBehavior()->withName('default');
        }
        return $this->behavior;
    }
}
