<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Hooks;

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

use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

/**
 * Purges previously added form files from items for context menus.
 * @internal
 */
class FormFileProvider extends FileProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [];

    /**
     * Lowest priority, thus gets executed last.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * @return bool
     */
    public function canHandle(): bool
    {
        return parent::canHandle()
            && StringUtility::endsWith($this->identifier, FormPersistenceManager::FORM_DEFINITION_FILE_EXTENSION);
    }

    /**
     * @param array $items
     * @return array
     */
    public function addItems(array $items): array
    {
        parent::initialize();
        return $this->purgeItems($items);
    }

    /**
     * Purges items that are not allowed for according command.
     * According canBeEdited, canBeRenamed, ... commands will always return
     * false in order to remove those form file items.
     *
     * Using the canRender() approach avoid adding hardcoded index name
     * lookup. Thus, it's streamlined with the rest of the provides, but
     * actually purges items instead of adding them.
     *
     * @param array $items
     * @return array
     */
    protected function purgeItems(array $items): array
    {
        foreach ($items as $name => $item) {
            $type = $item['type'];

            if ($type === 'submenu' && !empty($item['childItems'])) {
                $item['childItems'] = $this->purgeItems($item['childItems']);
            } elseif (!parent::canRender($name, $type)) {
                unset($items[$name]);
            }
        }

        return $items;
    }

    /**
     * @return bool
     */
    protected function canBeEdited(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    protected function canBeRenamed(): bool
    {
        return false;
    }
}
