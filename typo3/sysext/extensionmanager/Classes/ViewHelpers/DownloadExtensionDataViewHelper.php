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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * view helper for displaying a download extension data link
 * @internal
 */
class DownloadExtensionDataViewHelper extends Link\ActionViewHelper
{
    /**
     * Initialize arguments
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('extension', 'array', '', true);
    }

    /**
     * Renders an install link
     *
     * @return string the rendered a tag
     */
    public function render()
    {
        $extension = $this->arguments['extension'];
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $filePrefix = PATH_site . $extension['siteRelPath'];
        if (!file_exists(($filePrefix . 'ext_tables.sql')) && !file_exists(($filePrefix . 'ext_tables_static+adt.sql'))) {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        }
        $uriBuilder = $this->controllerContext->getUriBuilder();
        $uriBuilder->reset();
        $uri = $uriBuilder->uriFor('downloadExtensionData', [
            'extension' => $extension['key']
        ], 'Action');
        $this->tag->addAttribute('href', $uri);
        $cssClass = 'downloadExtensionData btn btn-default';
        $this->tag->addAttribute('class', $cssClass);
        $this->tag->addAttribute('title', \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.downloadsql', 'extensionmanager'));
        $this->tag->setContent($iconFactory->getIcon('actions-system-extension-sqldump', Icon::SIZE_SMALL)->render());
        return $this->tag->render();
    }
}
