<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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

/**
 * Display a link to show all versions of an extension
 * @internal
 */
class ShowExtensionVersionsViewHelper extends Link\ActionViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Renders an install link
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
     * @return string the rendered a tag
     */
    public function render($extension)
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();
        $action = 'showAllVersions';
        $uri = $uriBuilder->reset()->uriFor($action, [
            'extensionKey' => $extension->getExtensionKey(),
        ], 'List');
        $this->tag->addAttribute('href', $uri);

        // Set class
        $this->tag->addAttribute('class', 'versions-all ui-icon ui-icon-triangle-1-s');

        $label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.showAllVersions.label', 'extensionmanager');
        $this->tag->addAttribute('title', $label);
        $this->tag->setContent($label);
        return $this->tag->render();
    }
}
