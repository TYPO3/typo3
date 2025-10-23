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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\FullyRenderedButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\PositionInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Container for managing buttons in the backend module document header.
 * The ButtonBar organizes buttons into two positions (left and right) with multiple groups,
 * allowing precise control over button placement and visual grouping.
 *
 * Buttons are organized in a three-level structure:
 * 1. Position: LEFT or RIGHT side of the header
 * 2. Group: Numerical groups within each position (1, 2, 3, etc.)
 * 3. Individual buttons within each group
 *
 * Groups are rendered with visual spacing between them, allowing logical grouping
 * of related actions. Lower group numbers appear first (left-to-right, or top-to-bottom on mobile).
 *
 * Example - Using pre-configured buttons from ComponentFactory:
 *
 * ```
 * public function __construct(
 *     protected readonly ComponentFactory $componentFactory,
 * ) {}
 *
 * public function myAction(): ResponseInterface
 * {
 *     $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 *
 *     // Use pre-configured save button
 *     $saveButton = $this->componentFactory->createSaveButton('editform');
 *     $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 *
 *     // Use pre-configured back button
 *     $backButton = $this->componentFactory->createBackButton($returnUrl);
 *     $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
 * }
 * ```
 *
 * Example - Creating custom buttons with ComponentFactory:
 *
 * ```
 * public function __construct(
 *     protected readonly ComponentFactory $componentFactory,
 * ) {}
 *
 * public function myAction(): ResponseInterface
 * {
 *     $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 *
 *     // Create custom link button
 *     $customButton = $this->componentFactory->createLinkButton()
 *         ->setHref('/custom-action')
 *         ->setTitle('Custom Action')
 *         ->setIcon($iconFactory->getIcon('actions-custom'));
 *     $buttonBar->addButton($customButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 * }
 * ```
 *
 * Example - Buttons with automatic positioning:
 *
 * ```
 * // ShortcutButton implements PositionInterface and positions itself automatically
 * $shortcutButton = $this->componentFactory->createShortcutButton()
 *     ->setRouteIdentifier('my_module')
 *     ->setDisplayName('My Module');
 * $buttonBar->addButton($shortcutButton); // Position and group are automatic
 * ```
 *
 * Example - Dropdown button:
 *
 * ```
 * $dropdownButton = $this->componentFactory->createDropDownButton()
 *     ->setLabel('Actions')
 *     ->setIcon($iconFactory->getIcon('actions-menu'));
 *
 * $item1 = GeneralUtility::makeInstance(DropDownItem::class)
 *     ->setLabel('Edit')
 *     ->setHref('/edit');
 * $dropdownButton->addItem($item1);
 *
 * $item2 = GeneralUtility::makeInstance(DropDownItem::class)
 *     ->setLabel('Delete')
 *     ->setHref('/delete');
 * $dropdownButton->addItem($item2);
 *
 * $buttonBar->addButton($dropdownButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);
 * ```
 *
 * @phpstan-type Buttons array<self::BUTTON_POSITION_*, array<int, list<ButtonInterface>>>
 */
#[Autoconfigure(public: true)]
class ButtonBar
{
    /**
     * Position constant for left side of the button bar
     */
    public const BUTTON_POSITION_LEFT = 'left';

    /**
     * Position constant for right side of the button bar
     */
    public const BUTTON_POSITION_RIGHT = 'right';

    /**
     * Internal array of all registered buttons
     *
     * @var Buttons
     */
    protected array $buttons = [];

    public function __construct(
        protected readonly ComponentFactory $componentFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Add a new button
     *
     * Buttons implementing PositionInterface will automatically use their own
     * predefined position and group, ignoring the $buttonPosition and $buttonGroup
     * parameters. This ensures buttons like ShortcutButton always appear in their
     * designated location.
     *
     * @param ButtonInterface $button The Button Object to add
     * @param self::BUTTON_POSITION_* $buttonPosition Position of the button (left/right). Ignored if button implements PositionInterface.
     * @param int $buttonGroup Buttongroup of the button. Ignored if button implements PositionInterface.
     *
     * @throws \InvalidArgumentException In case a button is not valid
     */
    public function addButton(
        ButtonInterface $button,
        string $buttonPosition = self::BUTTON_POSITION_LEFT,
        int $buttonGroup = 1
    ): static {
        if (!$button->isValid()) {
            throw new \InvalidArgumentException('Button "' . $button->getType() . '" is not valid', 1441706370);
        }
        // Buttons implementing PositionInterface define their own position and group
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
     * @throws \InvalidArgumentException In case a ButtonClass does not implement ButtonInterface
     * @deprecated since v14, will be removed in v15. Use GeneralUtility::makeInstance() directly or inject ComponentFactory and use its create*() methods.
     */
    public function makeButton(string $button): ButtonInterface
    {
        trigger_error('ButtonBar::makeButton() is deprecated and will be removed in TYPO3 v15. Use GeneralUtility::makeInstance() directly or inject ComponentFactory and use its create*() methods.', E_USER_DEPRECATED);
        if (!in_array(ButtonInterface::class, class_implements($button) ?: [], true)) {
            throw new \InvalidArgumentException('A Button must implement ButtonInterface', 1441706378);
        }
        return GeneralUtility::makeInstance($button);
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
        $this->buttons = $this->eventDispatcher->dispatch(new ModifyButtonBarEvent($this->buttons, $this))->getButtons();

        return $this->buttons;
    }

    /**
     * @deprecated since v14, will be removed in v15. Inject ComponentFactory and use ComponentFactory::createGenericButton() instead.
     */
    public function makeGenericButton(): GenericButton
    {
        // @todo Activate once core is migrated
        // trigger_error('ButtonBar::makeGenericButton() is deprecated, inject ComponentFactory and use ComponentFactory::createGenericButton() instead.', E_USER_DEPRECATED);
        return $this->componentFactory->createGenericButton();
    }

    /**
     * @deprecated since v14, will be removed in v15. Inject ComponentFactory and use ComponentFactory::createInputButton() instead.
     */
    public function makeInputButton(): InputButton
    {
        // @todo Activate once core is migrated
        // trigger_error('ButtonBar::makeInputButton() is deprecated, inject ComponentFactory and use ComponentFactory::createInputButton() instead.', E_USER_DEPRECATED);
        return $this->componentFactory->createInputButton();
    }

    /**
     * @deprecated since v14, will be removed in v15. Inject ComponentFactory and use ComponentFactory::createSplitButton() instead.
     */
    public function makeSplitButton(): SplitButton
    {
        // @todo Activate once core is migrated
        // trigger_error('ButtonBar::makeSplitButton() is deprecated, inject ComponentFactory and use ComponentFactory::createSplitButton() instead.', E_USER_DEPRECATED);
        return $this->componentFactory->createSplitButton();
    }

    /**
     * @deprecated since v14, will be removed in v15. Inject ComponentFactory and use ComponentFactory::createDropDownButton() instead.
     */
    public function makeDropDownButton(): DropDownButton
    {
        // @todo Activate once core is migrated
        // trigger_error('ButtonBar::makeDropDownButton() is deprecated, inject ComponentFactory and use ComponentFactory::createDropDownButton() instead.', E_USER_DEPRECATED);
        return $this->componentFactory->createDropDownButton();
    }

    /**
     * @deprecated since v14, will be removed in v15. Inject ComponentFactory and use ComponentFactory::createLinkButton() instead.
     */
    public function makeLinkButton(): LinkButton
    {
        // @todo Activate once core is migrated
        // trigger_error('ButtonBar::makeLinkButton() is deprecated, inject ComponentFactory and use ComponentFactory::createLinkButton() instead.', E_USER_DEPRECATED);
        return $this->componentFactory->createLinkButton();
    }

    /**
     * @deprecated since v14, will be removed in v15. Inject ComponentFactory and use ComponentFactory::createFullyRenderedButton() instead.
     */
    public function makeFullyRenderedButton(): FullyRenderedButton
    {
        // @todo Activate once core is migrated
        // trigger_error('ButtonBar::makeFullyRenderedButton() is deprecated, inject ComponentFactory and use ComponentFactory::createFullyRenderedButton() instead.', E_USER_DEPRECATED);
        return $this->componentFactory->createFullyRenderedButton();
    }

    /**
     * @deprecated since v14, will be removed in v15. Inject ComponentFactory and use ComponentFactory::createShortcutButton() instead.
     */
    public function makeShortcutButton(): ShortcutButton
    {
        // @todo Activate once core is migrated
        // trigger_error('ButtonBar::makeShortcutButton() is deprecated, inject ComponentFactory and use ComponentFactory::createShortcutButton() instead.', E_USER_DEPRECATED);
        return $this->componentFactory->createShortcutButton();
    }
}
