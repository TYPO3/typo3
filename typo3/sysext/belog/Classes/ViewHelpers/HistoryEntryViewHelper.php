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
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Get history entry from for log entry
 * @internal
 */
class HistoryEntryViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Get system history record
     *
     * @param int $uid Uid of the log entry
     * @return string Formatted history entry if one exists, else empty string
     */
    public function render($uid)
    {
        return static::renderStatic(
            [
                'uid' => $uid
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var \TYPO3\CMS\Belog\Domain\Repository\HistoryEntryRepository $historyEntryRepository */
        $historyEntryRepository = $objectManager->get(HistoryEntryRepository::class);
        /** @var \TYPO3\CMS\Belog\Domain\Model\HistoryEntry $historyEntry */
        $historyEntry = $historyEntryRepository->findOneBySysLogUid($arguments['uid']);
        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext */
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
        return $historyLabel . '&nbsp;' . $historyLink;
    }
}
