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
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * View helper for configure extension link
 * @internal
 */
class ConfigureExtensionViewHelper extends Link\ActionViewHelper
{
    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('extension', 'array', 'Extension configuration array with extension information', true);
        $this->registerArgument('forceConfiguration', 'bool', 'If TRUE the content is only returned if a link could be generated', false, true);
        $this->registerArgument('showDescription', 'bool', 'If TRUE the extension description is also shown in the title attribute', false, false);
    }

    /**
     * Renders a configure extension link if the extension has configuration options
     *
     * @return string the rendered tag or child nodes content
     */
    public function render()
    {
        $extension = $this->arguments['extension'];
        $forceConfiguration = $this->arguments['forceConfiguration'];
        $showDescription = $this->arguments['showDescription'];

        $content = (string)$this->renderChildren();
        if ($extension['installed'] && file_exists(PATH_site . $extension['siteRelPath'] . 'ext_conf_template.txt')) {
            $uriBuilder = $this->controllerContext->getUriBuilder();
            $action = 'showConfigurationForm';
            $uri = $uriBuilder->reset()->uriFor(
                $action,
                ['extension' => ['key' => $extension['key']]],
                'Configuration'
            );
            if ($showDescription) {
                $title = $extension['description'] . PHP_EOL .
                    LocalizationUtility::translate('extensionList.clickToConfigure', 'extensionmanager');
            } else {
                $title = LocalizationUtility::translate('extensionList.configure', 'extensionmanager');
            }
            $this->tag->addAttribute('href', $uri);
            $this->tag->addAttribute('title', $title);
            $this->tag->setContent($content);
            $content = $this->tag->render();
        } elseif ($forceConfiguration) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $content = '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        } else {
            $content = '<span title="' . htmlspecialchars($extension['description']) . '">' . $content . '</span>';
        }

        return $content;
    }
}
