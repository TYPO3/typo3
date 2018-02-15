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
use TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper;

/**
 * view helper for displaying a remove extension link
 * @internal
 */
class RemoveExtensionViewHelper extends ActionViewHelper
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
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extension['key'])) {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        }
        if (
            !in_array($extension['type'], \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::returnAllowedInstallTypes()) ||
            $extension['type'] === 'System'
        ) {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        }
        $uriBuilder = $this->renderingContext->getControllerContext()->getUriBuilder();
        $action = 'removeExtension';
        $uriBuilder->reset();
        $uriBuilder->setFormat('json');
        $uri = $uriBuilder->uriFor($action, [
            'extension' => $extension['key']
        ], 'Action');
        $this->tag->addAttribute('href', $uri);
        $cssClass = 'removeExtension btn btn-default';
        $this->tag->addAttribute('class', $cssClass);
        $this->tag->addAttribute('title', \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.remove', 'extensionmanager'));
        $this->tag->setContent($iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render());
        return $this->tag->render();
    }
}
