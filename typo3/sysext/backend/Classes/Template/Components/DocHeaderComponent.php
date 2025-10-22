<?php

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

namespace TYPO3\CMS\Backend\Template\Components;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbContext;
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbFactory;
use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DocHeader component class
 */
class DocHeaderComponent
{
    /**
     * MenuRegistry Object
     *
     * @var MenuRegistry
     */
    protected $menuRegistry;

    /**
     * Meta information
     *
     * @var MetaInformation
     */
    protected $metaInformation;

    /**
     * Registry Container for Buttons
     *
     * @var ButtonBar
     */
    protected $buttonBar;

    protected Breadcrumb $breadcrumb;

    protected BreadcrumbFactory $breadcrumbFactory;

    /**
     * The breadcrumb context for the current request.
     */
    protected ?BreadcrumbContext $breadcrumbContext = null;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * Sets up buttonBar and MenuRegistry
     */
    public function __construct()
    {
        $this->buttonBar = GeneralUtility::makeInstance(ButtonBar::class);
        $this->menuRegistry = GeneralUtility::makeInstance(MenuRegistry::class);
        $this->metaInformation = GeneralUtility::makeInstance(MetaInformation::class);
        $this->breadcrumb = GeneralUtility::makeInstance(Breadcrumb::class);
        $this->breadcrumbFactory = GeneralUtility::makeInstance(BreadcrumbFactory::class);
    }

    /**
     * Set page information
     *
     * @param array $metaInformation Record array
     * @deprecated since v14, will be removed in v15. Use setPageBreadcrumb() instead.
     */
    public function setMetaInformation(array $metaInformation)
    {
        trigger_error(
            'DocHeaderComponent::setMetaInformation() is deprecated and will be removed in TYPO3 v15. Use setPageBreadcrumb() instead.',
            E_USER_DEPRECATED
        );

        $this->metaInformation->setRecordArray($metaInformation);

        // Migrate meta information for breadcrumbs
        if (isset($metaInformation['uid'])) {
            $recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
            $record = $recordFactory->createResolvedRecordFromDatabaseRow('pages', $metaInformation);
            $this->breadcrumbContext = new BreadcrumbContext($record, []);
        }
    }

    /**
     * Set meta information for a file/folder resource.
     *
     * @deprecated since v14, will be removed in v15. Use setResourceBreadcrumb() instead.
     */
    public function setMetaInformationForResource(ResourceInterface $resource): void
    {
        trigger_error(
            'DocHeaderComponent::setMetaInformationForResource() is deprecated and will be removed in TYPO3 v15. Use setResourceBreadcrumb() instead.',
            E_USER_DEPRECATED
        );

        $this->metaInformation->setResource($resource);

        // Migrate meta information for breadcrumbs
        $this->breadcrumbContext = new BreadcrumbContext($resource, []);
    }

    /**
     * Sets the breadcrumb context for rendering.
     *
     * This is the main API for providing breadcrumb information.
     *
     * For common scenarios, use the convenience methods instead:
     * - setPageBreadcrumb() for page records
     * - setRecordBreadcrumb() for any record
     * - setResourceBreadcrumb() for files or folders
     *
     * @param BreadcrumbContext|null $breadcrumbContext The breadcrumb context
     */
    public function setBreadcrumbContext(?BreadcrumbContext $breadcrumbContext): void
    {
        $this->breadcrumbContext = $breadcrumbContext;
    }

    /**
     * Sets breadcrumb from a page record array.
     *
     * This is the direct replacement for setMetaInformation().
     *
     * Example:
     *     $view->getDocHeaderComponent()->setPageBreadcrumb($pageInfo);
     *
     * @param array $pageRecord The page record array (must contain 'uid')
     */
    public function setPageBreadcrumb(array $pageRecord): void
    {
        $this->breadcrumbContext = $this->breadcrumbFactory->forPageArray($pageRecord);
    }

    /**
     * Sets breadcrumb for editing a record.
     *
     * Example:
     *     $view->getDocHeaderComponent()->setRecordBreadcrumb('tt_content', 123);
     *
     * @param string $table The table name
     * @param int $uid The record UID
     */
    public function setRecordBreadcrumb(string $table, int $uid): void
    {
        $this->breadcrumbContext = $this->breadcrumbFactory->forEditAction($table, $uid);
    }

    /**
     * Sets breadcrumb for any resource (file or folder).
     *
     * Example:
     *     $view->getDocHeaderComponent()->setResourceBreadcrumb($file);
     *     $view->getDocHeaderComponent()->setResourceBreadcrumb($folder);
     *
     * @param ResourceInterface $resource The resource (file or folder)
     */
    public function setResourceBreadcrumb(ResourceInterface $resource): void
    {
        $this->breadcrumbContext = $this->breadcrumbFactory->forResource($resource);
    }

    /**
     * Adds a suffix node to the current breadcrumb context.
     *
     * Suffix nodes are appended after the main breadcrumb trail and are useful for:
     * - Indicating "Create New" actions
     * - Showing "Edit Multiple" states
     * - Adding custom contextual information
     *
     * Note: This creates or modifies the breadcrumb context. If you need to build
     * a complete context, use BreadcrumbFactory instead.
     *
     * @param BreadcrumbNode $node The node to append
     */
    public function addBreadcrumbSuffixNode(BreadcrumbNode $node): void
    {
        if ($this->breadcrumbContext === null) {
            $this->breadcrumbContext = new BreadcrumbContext(null, [$node]);
        } else {
            // Create new context with added suffix node
            $existingSuffixNodes = $this->breadcrumbContext->suffixNodes;
            $existingSuffixNodes[] = $node;
            $this->breadcrumbContext = new BreadcrumbContext(
                $this->breadcrumbContext->mainContext,
                $existingSuffixNodes
            );
        }
    }

    /**
     * Get moduleMenuRegistry
     *
     * @return MenuRegistry
     */
    public function getMenuRegistry()
    {
        return $this->menuRegistry;
    }

    /**
     * Get ButtonBar
     *
     * @return ButtonBar
     */
    public function getButtonBar()
    {
        return $this->buttonBar;
    }

    /**
     * Determines whether this components is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Sets the enabled property to TRUE.
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Sets the enabled property to FALSE (disabled).
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Returns the abstract content of the docHeader as an array
     *
     * @return array
     */
    public function docHeaderContent(?ServerRequestInterface $request)
    {
        return [
            'enabled' => $this->isEnabled(),
            'buttons' => $this->buttonBar->getButtons(),
            'menus' => $this->menuRegistry->getMenus(),
            'breadcrumb' => $this->breadcrumb->getBreadcrumb($request, $this->breadcrumbContext),
        ];
    }
}
