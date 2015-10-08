<?php
namespace TYPO3\CMS\Backend\Controller;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for redirecting shortcut actions to the correct script
 * @deprecated since TYPO3 CMS 7, this file will be removed in TYPO3 CMS 8, this logic is not needed anymore
 */
class ListFrameLoaderController
{
    /**
     * @var string
     */
    protected $content;

    /**
     * Main content generated
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function main()
    {
        GeneralUtility::logDeprecatedFunction();
        $GLOBALS['TBE_TEMPLATE']->divClass = '';
        $this->content .= $this->getDocumentTemplate()->startPage('List Frame Loader');
        $this->content .= $this->getDocumentTemplate()->wrapScriptTags('
			var theUrl = top.getModuleUrl("");
			if (theUrl)	window.location.href=theUrl;
		');
        // End page:
        $this->content .= $this->getDocumentTemplate()->endPage();
        // Output:
        echo $this->content;
    }

    /**
     * Returns an instance of DocumentTemplate
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }
}
