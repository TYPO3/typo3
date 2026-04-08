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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Behavior\NodeInterface;
use TYPO3\HtmlSanitizer\Builder\CommonBuilder;
use TYPO3\HtmlSanitizer\Context;
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
        $isOnCurrentHostAttr = new Behavior\ClosureAttrValue(
            // @todo: This closure has a late dependency to $GLOBALS['TYPO3_REQUEST'] that should eventually
            //        be made explicit, probably by handing over ServerRequestInterface to build().
            static function (string $value): bool {
                /** @var ServerRequestInterface|null $request */
                $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
                if ($request === null) {
                    throw new \RuntimeException('DefaultSanitizerBuilder requires an active PSR-7 request with normalizedParams attribute.', 1775675289);
                }
                return GeneralUtility::isValidUrl($value) && GeneralUtility::isOnCurrentHost($value, $request)
                    || PathUtility::isAbsolutePath($value) && GeneralUtility::isAllowedAbsPath($value);
            }
        );
        // + starting with `t3://`
        $isTypo3Uri = new Behavior\RegExpAttrValue('#^t3://#');

        // extends common attributes for TYPO3-specific URIs
        $this->srcAttr->addValues($isOnCurrentHostAttr);
        $this->hrefAttr->addValues($isOnCurrentHostAttr, $isTypo3Uri);

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
            $this->behavior = parent::createBehavior()
                ->withName('default')
                ->withNodes(new Behavior\NodeHandler(
                    new Behavior\Tag('svg'),
                    new Behavior\Handler\ClosureHandler(
                        static function (NodeInterface $node, ?\DOMNode $domNode, Context $context): ?\DOMNode {
                            if ($domNode === null) {
                                return null;
                            }

                            $newNode = GeneralUtility::makeInstance(SvgSanitizer::class)
                                ->sanitizeNode($domNode);

                            // purge empty svg nodes
                            if ($newNode->childNodes->length === 0) {
                                return null;
                            }

                            $fragment = $domNode->ownerDocument->createDocumentFragment();
                            $fragment->append($newNode);
                            return $fragment;
                        }
                    )
                ));
        }
        return $this->behavior;
    }
}
