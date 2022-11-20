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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\Action\HelpButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\FullyRenderedButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\PositionInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Bar holding the buttons
 */
class ButtonBar
{
    /**
     * Identifier for the left button bar
     */
    public const BUTTON_POSITION_LEFT = 'left';

    /**
     * Identifier for the right button bar
     */
    public const BUTTON_POSITION_RIGHT = 'right';

    /**
     * Internal array of all registered buttons
     *
     * @var array
     */
    protected $buttons = [];

    /**
     * Add button
     *
     * @param ButtonInterface $button The Button Object to add
     * @param string $buttonPosition Position of the button (left/right)
     * @param int $buttonGroup Buttongroup of the button
     *
     * @throws \InvalidArgumentException In case a button is not valid
     *
     * @return $this
     */
    public function addButton(
        ButtonInterface $button,
        $buttonPosition = self::BUTTON_POSITION_LEFT,
        $buttonGroup = 1
    ) {
        if (!$button->isValid()) {
            throw new \InvalidArgumentException('Button "' . $button->getType() . '" is not valid', 1441706370);
        }
        // Determine the default button position
        if ($button instanceof PositionInterface) {
            $buttonPosition = $button->getPosition();
            $buttonGroup = $button->getGroup();
        }
        // We make the button immutable here
        $this->buttons[$buttonPosition][$buttonGroup][] = clone $button;
        return $this;
    }

    /**
     * Creates a new button of the given type
     *
     * @param string $button ButtonClass to invoke. Must implement ButtonInterface
     *
     * @throws \InvalidArgumentException In case a ButtonClass does not implement
     * ButtonInterface
     *
     * @return ButtonInterface
     */
    public function makeButton($button)
    {
        if (!in_array(ButtonInterface::class, class_implements($button) ?: [], true)) {
            throw new \InvalidArgumentException('A Button must implement ButtonInterface', 1441706378);
        }
        return GeneralUtility::makeInstance($button);
    }

    /**
     * Creates a new InputButton
     *
     * @return InputButton
     */
    public function makeInputButton()
    {
        return GeneralUtility::makeInstance(InputButton::class);
    }

    /**
     * Creates a new SplitButton
     *
     * @return SplitButton
     */
    public function makeSplitButton()
    {
        return GeneralUtility::makeInstance(SplitButton::class);
    }

    /**
     * Creates a new DropDownButton
     *
     * @return DropDownButton
     */
    public function makeDropDownButton()
    {
        return GeneralUtility::makeInstance(DropDownButton::class);
    }

    /**
     * Creates a new LinkButton
     *
     * @return LinkButton
     */
    public function makeLinkButton()
    {
        return GeneralUtility::makeInstance(LinkButton::class);
    }

    /**
     * Creates a new FullyRenderedButton
     *
     * @return FullyRenderedButton
     */
    public function makeFullyRenderedButton()
    {
        return GeneralUtility::makeInstance(FullyRenderedButton::class);
    }

    /**
     * Creates a new ShortcutButton
     *
     * @return ShortcutButton
     */
    public function makeShortcutButton()
    {
        return GeneralUtility::makeInstance(ShortcutButton::class);
    }

    /**
     * Creates a new HelpButton
     *
     * @return HelpButton
     *
     * @deprecated The functionality has been removed in v12. The method will be removed in TYPO3 v13.
     */
    public function makeHelpButton()
    {
        trigger_error(
            'Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13.'
            . ' Context Sensitive Help has been removed in v12.',
            E_USER_DEPRECATED
        );
        return GeneralUtility::makeInstance(HelpButton::class);
    }

    /**
     * Returns an associative array of all buttons in the form of
     * ButtonPosition > ButtonGroup > Button
     */
    public function getButtons(): array
    {
        // here we need to call the sorting methods and stuff.
        foreach ($this->buttons as $position => $_) {
            ksort($this->buttons[$position]);
        }

        // Dispatch event for manipulating the docHeaderButtons
        $this->buttons = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new ModifyButtonBarEvent($this->buttons, $this)
        )->getButtons();

        return $this->buttons;
    }
}
