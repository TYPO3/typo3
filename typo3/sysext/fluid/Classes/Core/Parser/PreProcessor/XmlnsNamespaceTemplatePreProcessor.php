<?php
namespace TYPO3\CMS\Fluid\Core\Parser\PreProcessor;

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

use TYPO3Fluid\Fluid\Core\Parser\TemplateProcessorInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class XmlnsNamespaceTemplatePreProcessor
 */
class XmlnsNamespaceTemplatePreProcessor implements TemplateProcessorInterface
{
    /**
     * @var RenderingContextInterface
     */
    protected $renderingContext;

    /**
     * @param RenderingContextInterface $renderingContext
     * @return void
     */
    public function setRenderingContext(RenderingContextInterface $renderingContext)
    {
        $this->renderingContext = $renderingContext;
    }

    /**
     * Pre-process the template source before it is returned to the TemplateParser or passed to
     * the next TemplateProcessorInterface instance.
     *
     * Detects all tags that carry an `xmlns:` definition using a Fluid-compatible prefix and a
     * conventional namespace URL (http://typo3.org/ns/). Extracts the detected namespaces and
     * removes the detected tag.
     *
     * @param string $templateSource
     * @return string
     */
    public function preProcessSource($templateSource)
    {
        $matches = array();
        $namespacePattern = 'xmlns:([a-z0-9]+)="(http\\:\\/\\/typo3\\.org\\/ns\\/[^"]+)"';
        $matched = preg_match('/<([a-z0-9]+)(?:[^>]*?)\\s+' . $namespacePattern . '[^>]*>/', $templateSource, $matches);
        if ($matched) {
            $namespaces = array();
            preg_match_all('/' . $namespacePattern . '/', $matches[0], $namespaces, PREG_SET_ORDER);
            foreach ($namespaces as $set) {
                $namespaceUrl = $set[2];
                $namespaceUri = substr($namespaceUrl, 20);
                $namespacePhp = str_replace('/', '\\', $namespaceUri);
                $this->renderingContext->getViewHelperResolver()->addNamespace($set[1], $namespacePhp);
            }
            if (strpos($matches[0], 'data-namespace-typo3-fluid="true"')) {
                $templateSource = str_replace($matches[0], '', $templateSource);
                $closingTagName = $matches[1];
                $closingTag = '</' . $closingTagName . '>';
                if (strpos($templateSource, $closingTag)) {
                    $templateSource = substr($templateSource, 0, strrpos($templateSource, $closingTag)) .
                        substr($templateSource, strrpos($templateSource, $closingTag) + strlen($closingTag));
                }
            } else {
                if (!empty($namespaces)) {
                    $namespaceAttributesToRemove = [];
                    foreach ($namespaces as $namespace) {
                        $namespaceAttributesToRemove[] = preg_quote($namespace[1], '/') . '="' . preg_quote($namespace[2], '/') . '"';
                    }
                    $matchWithRemovedNamespaceAttributes = preg_replace('/(?:\\s*+xmlns:(?:' . implode('|', $namespaceAttributesToRemove) . ')\\s*+)++/', ' ', $matches[0]);
                    $templateSource = str_replace($matches[0], $matchWithRemovedNamespaceAttributes, $templateSource);
                }
            }
        }
        return $templateSource;
    }
}
