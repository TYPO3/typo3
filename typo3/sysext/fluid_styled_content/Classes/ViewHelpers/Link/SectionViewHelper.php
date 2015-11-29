<?php
namespace TYPO3\CMS\FluidStyledContent\ViewHelpers\Link;

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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * A view helper for creating a link to a section.
 * If baseUrl is used, the path part of the current URL is prefixed.
 *
 * = Example =
 *
 * <code title="section link">
 * <ce:link.section name="section">Jump to section</ce:link.section>
 * </code>
 *
 * <output>
 * <a href="#section">Jump to section</a> or
 * <a href="<path part of current URL>#section">Jump to section</a>
 * </output>
 */
class SectionViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
    }

    /**
     * Render the view helper
     *
     * @param string $name The section name to be used
     * @return string
     */
    public function render($name)
    {
        $fragment = '#' . $name;

        // Prefix with current URL path if baseUrl is used
        if (!empty($this->getTypoScriptFrontendController()->baseUrl)) {
            $fragment = $this->getTypoScriptFrontendController()->cObj->getUrlToCurrentLocation() . $fragment;
        }

        $this->tag->addAttribute('href', $fragment);
        $this->tag->setContent($this->renderChildren());

        return $this->tag->render();
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
