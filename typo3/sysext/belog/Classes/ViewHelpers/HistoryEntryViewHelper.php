<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Belog\Domain\Model\HistoryEntry;
use TYPO3\CMS\Belog\Domain\Repository\HistoryEntryRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Get history entry from for log entry
 * @internal
 */
class HistoryEntryViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'int', 'Uid of the log entry', true);
    }

    /**
     * Get system history record
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string Formatted history entry if one exists, else empty string
     * @throws \InvalidArgumentException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if (!$renderingContext instanceof RenderingContext) {
            throw new \InvalidArgumentException('The given rendering context is not of type "TYPO3\CMS\Fluid\Core\Rendering\RenderingContext"', 1468363945);
        }
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var \TYPO3\CMS\Belog\Domain\Repository\HistoryEntryRepository $historyEntryRepository */
        $historyEntryRepository = $objectManager->get(HistoryEntryRepository::class);
        /** @var \TYPO3\CMS\Belog\Domain\Model\HistoryEntry $historyEntry */
        $historyEntry = $historyEntryRepository->findOneBySysLogUid($arguments['uid']);
        $controllerContext = $renderingContext->getControllerContext();
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        if (!$historyEntry instanceof HistoryEntry) {
            return '';
        }
        $historyLabel = LocalizationUtility::translate(
            'changesInFields',
            $controllerContext->getRequest()->getControllerExtensionName(),
            [$historyEntry->getFieldlist()]
        );
        $titleLable = LocalizationUtility::translate(
            'showHistory',
            $controllerContext->getRequest()->getControllerExtensionName()
        );
        $historyIcon = $iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL)->render();
        $historyHref = BackendUtility::getModuleUrl(
                'record_history',
                [
                    'sh_uid' => $historyEntry->getUid(),
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ]
            );
        $historyLink = '<a href="' . htmlspecialchars($historyHref) . '" title="' . htmlspecialchars($titleLable) . '">' . $historyIcon . '</a>';
        return htmlspecialchars($historyLabel) . '&nbsp;' . $historyLink;
    }
}
