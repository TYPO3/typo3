<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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

/**
 * View helper to let 3rd-party extensions process the list of available
 * actions for a given extension.
 * @internal
 */
class ProcessAvailableActionsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
    const SIGNAL_ProcessActions = 'processActions';

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('extension', 'string', '', true);
    }

    /**
     * Processes the list of actions.
     *
     * @return string the rendered list of actions
     */
    public function render()
    {
        $extension = $this->arguments['extension'];
        $html = $this->renderChildren();
        $actions = preg_split('#\\n\\s*#s', trim($html));

        $actions = $this->emitProcessActionsSignal($extension, $actions);

        return implode(' ', $actions);
    }

    /**
     * Emits a signal after the list of actions is processed
     *
     * @param string $extension
     * @param array $actions
     * @return array Modified action array
     */
    protected function emitProcessActionsSignal($extension, array $actions)
    {
        $this->signalSlotDispatcher->dispatch(
            __CLASS__,
            static::SIGNAL_ProcessActions,
            [
                $extension,
                &$actions,
            ]
        );
        return $actions;
    }
}
