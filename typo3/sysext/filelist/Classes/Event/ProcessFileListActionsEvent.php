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

namespace TYPO3\CMS\Filelist\Event;

use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\ComponentGroup;
use TYPO3\CMS\Backend\Template\Components\ComponentInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * Event fired to modify icons rendered for the file listings
 */
final class ProcessFileListActionsEvent
{
    public function __construct(
        private readonly ComponentGroup $primary,
        private readonly ComponentGroup $secondary,
        private readonly ResourceInterface $fileOrFolder,
        private readonly RequestInterface $request,
    ) {}

    public function getResource(): ResourceInterface
    {
        return $this->fileOrFolder;
    }

    public function isFile(): bool
    {
        return $this->fileOrFolder instanceof FileInterface;
    }

    /**
     * Add a new action or override an existing one. Latter is only possible,
     * in case $columnName is given. Otherwise, the column will be added with
     * a numeric index, which is generally not recommended. It's also possible
     * to define the position of an action with either the "before" or "after"
     * argument, while their value must be an existing action.
     *
     * Note: In case none or an invalid $group is provided, the new action will
     * be added to the secondary group.
     */
    public function setAction(
        ?ComponentInterface $action,
        string $actionName,
        ActionGroup $group = ActionGroup::secondary,
        string $before = '',
        string $after = '',
    ): void {
        if ($actionName === '') {
            throw new \Exception('You must provide a valid action name when adding a new action.', 1761584689);
        }

        $componentGroup = match ($group) {
            ActionGroup::primary => $this->primary,
            ActionGroup::secondary => $this->secondary,
        };
        $componentGroup->add($actionName, $action, $before, $after);
    }

    /**
     * Whether the action exists in the given group. In case none or
     * an invalid $group is provided, both groups will be checked.
     */
    public function hasAction(string $actionName, ?ActionGroup $group = null): bool
    {
        return match ($group) {
            ActionGroup::primary => $this->primary->has($actionName),
            ActionGroup::secondary => $this->secondary->has($actionName),
            null => $this->primary->has($actionName) || $this->secondary->has($actionName),
        };
    }

    /**
     * Get action by its name. In case the action exists in both groups
     * and none or an invalid $group is provided, the action from the
     * "primary" group will be returned.
     */
    public function getAction(string $actionName, ?ActionGroup $group = null): ?ComponentInterface
    {
        return match ($group) {
            ActionGroup::primary => $this->primary->get($actionName),
            ActionGroup::secondary => $this->secondary->get($actionName),
            null => $this->primary->get($actionName) ?? $this->secondary->get($actionName),
        };
    }

    /**
     * Remove action by its name. In case the action exists in both groups
     * and none or an invalid $group is provided, the action will be removed
     * from both groups.
     */
    public function removeAction(string $actionName, ?ActionGroup $group = null): void
    {
        if ($group === null) {
            $this->primary->remove($actionName);
            $this->secondary->remove($actionName);
            return;
        }
        match ($group) {
            ActionGroup::primary => $this->primary->remove($actionName),
            ActionGroup::secondary => $this->secondary->remove($actionName),
        };
    }

    public function moveActionTo(
        string $actionName,
        ActionGroup $group,
        string $before = '',
        string $after = '',
    ): void {
        if (!$this->hasAction($actionName)) {
            throw new \RuntimeException('The action "' . $actionName . '" does not exist and therefore cannot be moved.', 1761646465);
        }
        $action = $this->getAction($actionName);
        $this->removeAction($actionName);
        $this->setAction($action, $actionName, $group, $before, $after);
    }

    /**
     * Get the actions of a specific group
     */
    public function getActionGroup(ActionGroup $group): ComponentGroup
    {
        return match ($group) {
            ActionGroup::primary => $this->primary,
            ActionGroup::secondary => $this->secondary,
        };
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
