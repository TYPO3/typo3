<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Link;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A view helper for creating links to external targets.
 *
 * = Examples =
 *
 * <code>
 * <f:link.external uri="http://www.typo3.org" target="_blank">external link</f:link.external>
 * </code>
 * <output>
 * <a href="http://www.typo3.org" target="_blank">external link</a>
 * </output>
 *
 * <code title="custom default scheme">
 * <f:link.external uri="typo3.org" defaultScheme="ftp">external ftp link</f:link.external>
 * </code>
 * <output>
 * <a href="ftp://typo3.org">external ftp link</a>
 * </output>
 *
 * @api
 */
class ExternalViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Initialize arguments
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
    }

    /**
     * @param string $uri the URI that will be put in the href attribute of the rendered link tag
     * @param string $defaultScheme scheme the href attribute will be prefixed with if specified $uri does not contain a scheme already
     * @return string Rendered link
     * @api
     */
    public function render($uri, $defaultScheme = 'http')
    {
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if ($scheme === null && $defaultScheme !== '') {
            $uri = $defaultScheme . '://' . $uri;
        }
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
