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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Get workspace title from workspace id
 * @internal
 */
class WorkspaceTitleViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * First level cache of workspace titles
     *
     * @var array
     */
    protected static $workspaceTitleRuntimeCache = [];

    /**
     * Resolve workspace title from UID.
     *
     * @param int $uid UID of the workspace
     * @return string workspace title or UID
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
        $uid = $arguments['uid'];

        if (isset(static::$workspaceTitleRuntimeCache[$uid])) {
            return htmlspecialchars(static::$workspaceTitleRuntimeCache[$uid]);
        }

        if ($uid === 0) {
            static::$workspaceTitleRuntimeCache[$uid] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('live', $renderingContext->getControllerContext()->getRequest()->getControllerExtensionName());
        } elseif (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
            static::$workspaceTitleRuntimeCache[$uid] = '';
        } else {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
            $workspaceRepository = $objectManager->get(\TYPO3\CMS\Belog\Domain\Repository\WorkspaceRepository::class);
            /** @var $workspace \TYPO3\CMS\Belog\Domain\Model\Workspace */
            $workspace = $workspaceRepository->findByUid($uid);
            // $workspace may be null, force empty string in this case
            static::$workspaceTitleRuntimeCache[$uid] = ($workspace === null) ? '' : $workspace->getTitle();
        }

        return htmlspecialchars(static::$workspaceTitleRuntimeCache[$uid]);
    }
}
