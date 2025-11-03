<?php

declare(strict_types=1);

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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbContext;
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbFactory;
use TYPO3\CMS\Backend\Dto\Breadcrumb\BreadcrumbNode;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Document header component for backend modules.
 *
 * This component manages the header area of backend module views, providing:
 * - Breadcrumb navigation (via BreadcrumbContext)
 * - Button bar for action buttons (save, close, delete, etc.)
 * - Drop-down menus for module-specific actions
 *
 * The component can be enabled or disabled to control visibility of the entire
 * document header. It integrates with the ModuleTemplate to provide a consistent
 * header across all backend modules.
 *
 * Usage in a controller:
 *
 * ```
 * public function __construct(
 *     protected readonly ComponentFactory $componentFactory,
 * ) {}
 *
 * public function myAction(): ResponseInterface
 * {
 *     $view = $this->moduleTemplateFactory->create($request);
 *     $docHeader = $view->getDocHeaderComponent();
 *
 *     // Set breadcrumb for a page
 *     $docHeader->setPageBreadcrumb($pageInfo);
 *
 *     // Add action buttons using ComponentFactory
 *     $buttonBar = $docHeader->getButtonBar();
 *     $saveButton = $this->componentFactory->createSaveButton('editform');
 *     $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 * }
 * ```
 */
#[Autoconfigure(public: true)]
class DocHeaderComponent
{
    /**
     * Legacy meta information component (deprecated).
     *
     * @deprecated Will be removed in TYPO3 v15. Use breadcrumbContext instead.
     */
    protected MetaInformation $metaInformation;

    /**
     * Button bar component for managing action buttons.
     */
    protected ButtonBar $buttonBar;

    /**
     * Breadcrumb component for rendering navigation trails.
     */
    protected Breadcrumb $breadcrumb;

    /**
     * Context information for breadcrumb rendering.
     *
     * Contains the main context (page, record, or resource) and optional suffix nodes
     * for additional navigation elements.
     */
    protected ?BreadcrumbContext $breadcrumbContext = null;

    /**
     * Whether the document header is enabled and should be rendered.
     */
    protected bool $enabled = true;

    /**
     * Language selector component.
     */
    protected ?ComponentInterface $languageSelector = null;

    public function __construct(
        protected readonly MenuRegistry $menuRegistry,
        protected readonly RecordFactory $recordFactory,
        protected readonly BreadcrumbFactory $breadcrumbFactory,
        protected readonly ComponentFactory $componentFactory,
    ) {
        $this->buttonBar = GeneralUtility::makeInstance(ButtonBar::class);
        $this->metaInformation = GeneralUtility::makeInstance(MetaInformation::class);
        $this->breadcrumb = GeneralUtility::makeInstance(Breadcrumb::class);
    }

    /**
     * Set page information
     *
     * @param array $metaInformation Record array
     * @deprecated since v14, will be removed in v15. Use setPageBreadcrumb() instead.
     */
    public function setMetaInformation(array $metaInformation): void
    {
        trigger_error(
            'DocHeaderComponent::setMetaInformation() is deprecated and will be removed in TYPO3 v15. Use setPageBreadcrumb() instead.',
            E_USER_DEPRECATED
        );

        $this->metaInformation->setRecordArray($metaInformation);

        // Migrate meta information for breadcrumbs
        if (isset($metaInformation['uid'])) {
            $record = $this->recordFactory->createResolvedRecordFromDatabaseRow('pages', $metaInformation);
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
     * Example:
     *
     *     $docHeader->setPageBreadcrumb($pageInfo);
     *     $docHeader->addBreadcrumbSuffixNode(
     *         new BreadcrumbNode(
     *             identifier: 'new',
     *             label: 'Create New Content Element',
     *             icon: 'actions-add'
     *         )
     *     );
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
     * Returns the menu registry for adding drop-down menus to the document header.
     */
    public function getMenuRegistry(): MenuRegistry
    {
        return $this->menuRegistry;
    }

    /**
     * Returns the button bar for adding action buttons to the document header.
     *
     * The button bar supports multiple button positions (left, right) and groups
     * to organize buttons logically.
     */
    public function getButtonBar(): ButtonBar
    {
        return $this->buttonBar;
    }

    /**
     * Determines whether this component is enabled and should be rendered.
     *
     * When disabled, the entire document header (including breadcrumbs, buttons,
     * and menus) will not be displayed in the backend module.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enables this component for rendering.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disables this component to prevent rendering.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    public function setLanguageSelector(?ComponentInterface $component): void
    {
        $this->languageSelector = $component;
    }

    public function getLanguageSelector(): ?ComponentInterface
    {
        return $this->languageSelector;
    }

    /**
     * Returns the complete document header content as an array for rendering.
     *
     * This method aggregates all components (buttons, breadcrumbs) into
     * a structured array that can be consumed by the Fluid template rendering
     * the backend module layout.
     *
     * The returned array structure:
     * - 'enabled': Whether the document header should be rendered
     * - 'buttons': Array of button configurations from the button bar
     * - 'breadcrumb': Breadcrumb trail data from the breadcrumb context
     * - 'languageSelector': Language Selector
     */
    public function docHeaderContent(?ServerRequestInterface $request): array
    {
        // Process MenuRegistry and add any menus as dropdown buttons to the button bar
        $moduleMenuButton = $this->processMenuRegistry();
        if ($moduleMenuButton !== null) {
            $this->buttonBar->addButton($moduleMenuButton, ButtonBar::BUTTON_POSITION_LEFT, 0);
        }

        return [
            'enabled' => $this->isEnabled(),
            'buttons' => $this->buttonBar->getButtons(),
            'breadcrumb' => $this->breadcrumb->getBreadcrumb($request, $this->breadcrumbContext),
            'languageSelector' => $this->getLanguageSelector(),
        ];
    }

    /**
     * Processes registered menus from the MenuRegistry into a dropdown button component.
     *
     * Takes the first registered menu from the MenuRegistry and creates a dropdown button
     * component that can be added to the button bar.
     *
     * @return ButtonInterface|null The dropdown button, or null if no menus registered
     */
    private function processMenuRegistry(): ?ButtonInterface
    {
        $menus = $this->menuRegistry->getMenus();

        if ($menus === []) {
            return null;
        }

        // Use the first menu (most controllers only register one menu)
        $menu = reset($menus);

        // Hide menu if it's either empty or offers only one item
        if (count($menu->getMenuItems()) < 2) {
            return null;
        }

        $dropdownButton = $this->componentFactory->createDropDownButton()
            ->setLabel($menu->getLabel())
            ->setShowActiveLabelText(true)
            ->setShowLabelText(true);

        foreach ($menu->getMenuItems() as $menuItem) {
            $dropdownItem = $this->componentFactory->createDropDownRadio()
                ->setHref($menuItem->getHref())
                ->setLabel($menuItem->getTitle())
                ->setActive($menuItem->isActive());

            $dropdownButton->addItem($dropdownItem);
        }

        return $dropdownButton;
    }
}
