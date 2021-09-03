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
 * An event to modify the displayed record actions (e.g.
 * "edit", "copy", "delete") for a table in the RecordList.
 */
final class ModifyRecordListRecordActionsEvent
{
    private array $actions;
    private string $table;
    private array $record;
    private DatabaseRecordList $recordList;

    public function __construct(array $actions, string $table, array $record, DatabaseRecordList $recordList)
    {
        $this->actions = $actions;
        $this->table = $table;
        $this->record = $record;
        $this->recordList = $recordList;
    }

    /**
     * Add a new action or override an existing one. Latter is only possible,
     * in case $columnName is given. Otherwise, the column will be added with
     * a numeric index, which is generally not recommended. It's also possible
     * to define the position of an action with either the "before" or "after"
     * argument, while their value must be an existing action.
     *
     * Note: In case non or an invalid $group is provided, the new action will
     * be added to the secondary group.
     *
     * @param string $action
     * @param string $actionName
     * @param string $group
     * @param string $before
     * @param string $after
     */
    public function setAction(
        string $action,
        string $actionName = '',
        string $group = '',
        string $before = '',
        string $after = ''
    ): void {
        // Only "primary" and "secondary" are valid, default to "secondary" otherwise
        $group = in_array($group, ['primary', 'secondary'], true) ? $group : 'secondary';

        if ($actionName !== '') {
            if ($before !== '' && $this->hasAction($before, $group)) {
                $end = array_splice($this->actions[$group], (int)(array_search($before, array_keys($this->actions[$group]), true)));
                $this->actions[$group] = array_merge($this->actions[$group], [$actionName => $action], $end);
            } elseif ($after !== '' && $this->hasAction($after, $group)) {
                $end = array_splice($this->actions[$group], (int)(array_search($after, array_keys($this->actions[$group]), true)) + 1);
                $this->actions[$group] = array_merge($this->actions[$group], [$actionName => $action], $end);
            } else {
                $this->actions[$group][$actionName] = $action;
            }
        } else {
            $this->actions[$group][] = $action;
        }
    }

    /**
     * Whether the action exists in the given group. In case non or
     * an invalid $group is provided, both groups will be checked.
     *
     * @param string $actionName
     * @param string $group
     * @return bool
     */
    public function hasAction(string $actionName, string $group = ''): bool
    {
        if (in_array($group, ['primary', 'secondary'], true)) {
            return (bool)($this->actions[$group][$actionName] ?? false);
        }

        return (bool)($this->actions['primary'][$actionName] ?? $this->actions['secondary'][$actionName] ?? false);
    }

    /**
     * Get action by its name. In case the action exists in both groups
     * and non or an invalid $group is provided, the action from the
     * "primary" group will be returned.
     *
     * @param string $actionName
     * @param string $group
     * @return string|null
     */
    public function getAction(string $actionName, string $group = ''): ?string
    {
        if (in_array($group, ['primary', 'secondary'], true)) {
            return $this->actions[$group][$actionName] ?? null;
        }

        return $this->actions['primary'][$actionName] ?? $this->actions['secondary'][$actionName] ?? null;
    }

    /**
     * Remove action by its name. In case the action exists in both groups
     * and non or an invalid $group is provided, the action will be removed
     * from both groups.
     *
     * @param string $actionName
     * @param string $group
     * @return bool Whether the action could be removed - Will therefore
     *              return FALSE if the action to remove does not exist.
     */
    public function removeAction(string $actionName, string $group = ''): bool
    {
        if (($this->actions[$group][$actionName] ?? false) && in_array($group, ['primary', 'secondary'], true)) {
            unset($this->actions[$group][$actionName]);
            return true;
        }

        $actionRemoved = false;

        if ($this->actions['primary'][$actionName] ?? false) {
            unset($this->actions['primary'][$actionName]);
            $actionRemoved = true;
        }

        if ($this->actions['secondary'][$actionName] ?? false) {
            unset($this->actions['secondary'][$actionName]);
            $actionRemoved = true;
        }

        return $actionRemoved;
    }

    /**
     * Get the actions of a specific group
     *
     * @param string $group
     * @return array|null
     */
    public function getActionGroup(string $group): ?array
    {
        return in_array($group, ['primary', 'secondary'], true) ? $this->actions[$group] : null;
    }

    public function setActions(array $actions): void
    {
        $this->actions = $actions;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecord(): array
    {
        return $this->record;
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
