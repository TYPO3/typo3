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

namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Extensionmanager\Event\AvailableActionsForExtensionEvent;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper to let 3rd-party extensions process the list of available
 * actions for a given extension.
 * @internal
 */
class ProcessAvailableActionsViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('extension', 'array', '', true);
    }

    /**
     * Processes the list of actions.
     *
     * @return string the rendered list of actions
     */
    public function render()
    {
        $html = $this->renderChildren();
        $actions = preg_split('#\\n\\s*#s', trim($html));
        $actions = is_array($actions) ? $actions : [];

        $event = new AvailableActionsForExtensionEvent($this->arguments['extension']['key'], $this->arguments['extension'], $actions);
        $this->eventDispatcher->dispatch($event);
        return implode(' ', $event->getActions());
    }
}
