<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\ContextMenu\ItemProviders;

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

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract provider is a base class for context menu item providers
 */
class AbstractProvider implements ProviderInterface
{

    /**
     * Language Service property. Used to access localized labels
     *
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
     */
    protected $clipboard;

    /**
     * Array of items the class is providing
     *
     * @var array
     */
    protected $itemsConfiguration = [];

    /**
     * Click menu items disabled by TSConfig
     *
     * @var array
     */
    protected $disabledItems = [];

    /**
     * Current table name
     *
     * @var string
     */
    protected $table = '';

    /**
     * @var string clicked record identifier (usually uid or file combined identifier)
     */
    protected $identifier = '';

    /**
     * Context - from where the click menu was triggered (e.g. 'tree')
     *
     * @var string
     */
    protected $context = '';

    /**
     * Lightweight constructor, just to be able to call ->canHandle(). Rest of the initialization is done
     * in the initialize() method
     *
     * @param string $table
     * @param string $identifier
     * @param string $context
     */
    public function __construct(string $table, string $identifier, string $context = '')
    {
        $this->table = $table;
        $this->identifier = $identifier;
        $this->context = $context;
        $this->languageService = $GLOBALS['LANG'];
        $this->backendUser = $GLOBALS['BE_USER'];
    }

    /**
     * Provider initialization, heavy stuff
     */
    protected function initialize()
    {
        $this->initClipboard();
        $this->initDisabledItems();
    }

    /**
     * Returns the provider priority which is used for determining the order in which providers are adding items
     * to the result array. Highest priority means provider is evaluated first.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 100;
    }

    /**
     * Whether this provider can handle given request (usually a check based on table, uid and context)
     *
     * @return bool
     */
    public function canHandle(): bool
    {
        return false;
    }

    /**
     * Initialize clipboard object - necessary for all copy/cut/paste operations
     */
    protected function initClipboard()
    {
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard();
        // This locks the clipboard to the Normal for this request.
        $clipboard->lockToNormal();
        $this->clipboard = $clipboard;
    }

    /**
     * Fills $this->disabledItems with the values from TSConfig.
     * Disabled items can be set separately for each context.
     */
    protected function initDisabledItems()
    {
        if ($this->context) {
            $tsConfigValue = $this->backendUser->getTSConfig()['options.']['contextMenu.']['table.'][$this->table . '.'][$this->context . '.']['disableItems'] ?? '';
        } else {
            $tsConfigValue = $this->backendUser->getTSConfig()['options.']['contextMenu.']['table.'][$this->table . '.']['disableItems'] ?? '';
        }
        $this->disabledItems = GeneralUtility::trimExplode(',', $tsConfigValue, true);
    }

    /**
     * Adds new items to the given array or modifies existing items
     *
     * @param array $items
     * @return array
     */
    public function addItems(array $items): array
    {
        $this->initialize();
        $items += $this->prepareItems($this->itemsConfiguration);
        return $items;
    }

    /**
     * Converts item configuration (from $this->itemsConfiguration) into an array ready for returning by controller
     *
     * @param array $itemsConfiguration
     * @return array
     */
    protected function prepareItems(array $itemsConfiguration): array
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $items = [];
        foreach ($itemsConfiguration as $name => $configuration) {
            $type = !empty($configuration['type']) ? $configuration['type'] : 'item';
            if ($this->canRender($name, $type)) {
                $items[$name] = [
                    'type' => $type,
                    'label' => !empty($configuration['label']) ? htmlspecialchars($this->languageService->sL($configuration['label'])) : '',
                    'icon' => !empty($configuration['iconIdentifier']) ? $iconFactory->getIcon($configuration['iconIdentifier'], Icon::SIZE_SMALL)->render() : '',
                    'additionalAttributes' => $this->getAdditionalAttributes($name),
                    'callbackAction' => !empty($configuration['callbackAction']) ? $configuration['callbackAction'] : ''
                ];
                if ($type === 'submenu') {
                    $items[$name]['childItems'] = $this->prepareItems($configuration['childItems']);
                }
            }
        }
        return $items;
    }

    /**
     * Returns an array of additional attributes for given item. Additional attributes are used to pass item specific data
     * to the JS. E.g. message for the delete confirmation dialog
     *
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        return [];
    }

    /**
     * Checks whether certain item can be rendered (e.g. check for disabled items or permissions)
     *
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        return true;
    }

    /**
     * Returns a clicked record identifier
     *
     * @return string
     */
    protected function getIdentifier(): string
    {
        return '';
    }
}
