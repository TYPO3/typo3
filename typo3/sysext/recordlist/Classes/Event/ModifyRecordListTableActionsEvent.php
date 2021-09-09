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

namespace TYPO3\CMS\Recordlist\Event;

use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * An event to modify the multi record selection actions (e.g.
 * "edit", "copy to clipboard") for a table in the RecordList.
 */
final class ModifyRecordListTableActionsEvent
{
    private array $actions;
    private string $table;

    /**
     * @var int[]
     */
    private array $recordIds;

    private DatabaseRecordList $recordList;

    /**
     * The label, which will be displayed in case
     * no action is available for current the user.
     *
     * @var string
     */
    private string $noActionLabel = '';

    public function __construct(array $actions, string $table, array $recordIds, DatabaseRecordList $recordList)
    {
        $this->actions = $actions;
        $this->table = $table;
        $this->recordIds = $recordIds;
        $this->recordList = $recordList;
    }

    /**
     * Add a new action or override an existing one. Latter is only possible,
     * in case $actionName is given. Otherwise, the action will be added with
     * a numeric index, which is generally not recommended. It's also possible
     * to define the position of an action with either the "before" or "after"
     * argument, while their value must be an existing action.
     *
     * @param string $action
     * @param string $actionName
     * @param string $before
     * @param string $after
     */
    public function setAction(string $action, string $actionName = '', string $before = '', string $after = ''): void
    {
        if ($actionName !== '') {
            if ($before !== '' && $this->hasAction($before)) {
                $end = array_splice($this->actions, (int)(array_search($before, array_keys($this->actions), true)));
                $this->actions = array_merge($this->actions, [$actionName => $action], $end);
            } elseif ($after !== '' && $this->hasAction($after)) {
                $end = array_splice($this->actions, (int)(array_search($after, array_keys($this->actions), true)) + 1);
                $this->actions = array_merge($this->actions, [$actionName => $action], $end);
            } else {
                $this->actions[$actionName] = $action;
            }
        } else {
            $this->actions[] = $action;
        }
    }

    /**
     * Whether the action exists
     *
     * @param string $actionName
     * @return bool
     */
    public function hasAction(string $actionName): bool
    {
        return (bool)($this->actions[$actionName] ?? false);
    }

    /**
     * Get action by its name
     *
     * @param string $actionName
     * @return string|null The action or NULL if the action does not exist
     */
    public function getAction(string $actionName): ?string
    {
        return $this->actions[$actionName] ?? null;
    }

    /**
     * Remove action by its name
     *
     * @param string $actionName
     * @return bool Whether the action could be removed - Will therefore
     *              return FALSE if the action to remove does not exist.
     */
    public function removeAction(string $actionName): bool
    {
        if (!isset($this->actions[$actionName])) {
            return false;
        }
        unset($this->actions[$actionName]);
        return true;
    }

    public function setActions(array $actions): void
    {
        $this->actions = $actions;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function setNoActionLabel(string $noActionLabel): void
    {
        $this->noActionLabel = $noActionLabel;
    }

    /**
     * Get the label, which will be displayed, in case no
     * action is available for the current user. Note: If
     * this returns an empty string, this only means that
     * no other listener set a label before. TYPO3 will
     * always fall back to a default if this remains empty.
     *
     * @return string
     */
    public function getNoActionLabel(): string
    {
        return $this->noActionLabel;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecordIds(): array
    {
        return $this->recordIds;
    }

    /**
     * Returns the current DatabaseRecordList instance.
     *
     * @return DatabaseRecordList
     * @todo Might be replaced by a DTO in the future
     */
    public function getRecordList(): DatabaseRecordList
    {
        return $this->recordList;
    }
}
