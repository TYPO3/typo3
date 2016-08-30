<?php
namespace TYPO3\CMS\Documentation\Slots;

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
use TYPO3\CMS\Documentation\ViewHelpers\FormatsViewHelper;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;

/**
 * This slot listens to a signal in Extension Manager to add links to
 * manuals available locally.
 */
class ExtensionManager
{
    /**
     * @var \TYPO3\CMS\Documentation\Domain\Model\Document[]
     */
    protected static $documents = null;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Processes the list of actions for a given extension and adds
     * a link to the manual(s), if available.
     *
     * @param array $extension
     * @param array $actions
     * @return void
     */
    public function processActions(array $extension, array &$actions)
    {
        if (static::$documents === null) {
            /** @var \TYPO3\CMS\Documentation\Controller\DocumentController $documentController */
            $documentController = $this->objectManager->get(\TYPO3\CMS\Documentation\Controller\DocumentController::class);
            static::$documents = $documentController->getDocuments();
        }

        $extensionKey = $extension['key'];
        $documentKey = 'typo3cms.extensions.' . $extensionKey;

        if (isset(static::$documents[$documentKey])) {
            $document = static::$documents[$documentKey];

            foreach ($document->getTranslations() as $documentTranslation) {
                $actions[] = FormatsViewHelper::renderStatic(
                    [
                        'documentTranslation' => $documentTranslation,
                    ],
                    function () {
                    },
                    new RenderingContext()
                );
            }
        } else {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $emptyIcon = $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render();
            $actions[] = '<span class="btn btn-default disabled">' . $emptyIcon . '</span>';
            $actions[] = '<span class="btn btn-default disabled">' . $emptyIcon . '</span>';
            $actions[] = '<span class="btn btn-default disabled">' . $emptyIcon . '</span>';
        }
    }
}
