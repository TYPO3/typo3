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
 * ViewHelper for update script link
 * @internal
 */
class UpdateScriptViewHelper extends ActionViewHelper
{

    /** @var \TYPO3\CMS\Extbase\Object\ObjectManager */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('extensionKey', 'string', 'Extension key', true);
    }

    /**
     * Renders a link to the update script screen if the extension has one
     *
     * @return string The rendered a tag
     */
    public function render()
    {
        $extensionKey = $this->arguments['extensionKey'];

        // If the "class.ext_update.php" file exists, build link to the update script screen
        /** @var \TYPO3\CMS\Extensionmanager\Utility\UpdateScriptUtility $updateScriptUtility */
        $updateScriptUtility = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\UpdateScriptUtility::class);
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if ($updateScriptUtility->checkUpdateScriptExists($extensionKey)) {
            $uriBuilder = $this->renderingContext->getControllerContext()->getUriBuilder();
            $action = 'show';
            $uri = $uriBuilder->reset()->uriFor(
                $action,
                ['extensionKey' => $extensionKey],
                'UpdateScript'
            );
            $this->tag->addAttribute('href', $uri);
            $this->tag->addAttribute('title', \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.update.script', 'extensionmanager'));
            $this->tag->setContent($iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render());
            $tag = $this->tag->render();
        } else {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        }
        return $tag;
    }
}
