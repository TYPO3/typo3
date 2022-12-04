.. include:: /Includes.rst.txt

.. _feature-99093-1668065501:

====================================================
Feature: #99093 - Introduce DropDownButton component
====================================================

See :issue:`99093`

Description
===========

The module menu button bar now can display dropdowns.
This enables new interface interactions, like switching
the current view from list to tiles or group actions
like clipboard and thumbnail visibility. It make the views
clearer and the user to see more information at a glance.

Each dropdown consists of different items ranging from
headlines to item links that can display the current
status. The button automatically changes the icon
representation to the icon of the the first active radio
icon in the dropdown list.


DropDownButton
--------------

This button type is a container for dropdown items.
It will render a dropdown containing all items attached
to it. There are different kinds available, each item
needs to implement the
:php:`\TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItemInterface`.
When this type contains elements of type
:php:`\TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio` it
will use the icon of the first active item of this type.

..  code-block:: php

    $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
    $dropDownButton = $buttonBar->makeDropDownButton()
        ->setLabel('Dropdown')
        ->setTitle('Save')
        ->setIcon($this->iconFactory->getIcon('actions-heart'))
        ->addItem(
            GeneralUtility::makeInstance(DropDownItem::class)
                ->setLabel('Item')
                ->setHref('#')
        );
    $buttonBar->addButton($dropDownButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);


DropDown\DropDownDivider
------------------------

This dropdown item type renders the divider element.

..  code-block:: php

    // use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownDivider;

    $item = GeneralUtility::makeInstance(DropDownDivider::class);
    $dropDownButton->addItem($item);


DropDown\DropDownHeader
-----------------------

This dropdown item type renders a non-interactive text
element to group items and gives more meaning to a set
of options.

..  code-block:: php

    // use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownHeader;

    $item = GeneralUtility::makeInstance(DropDownHeader::class)
        ->setLabel('Label');
    $dropDownButton->addItem($item);


DropDown\DropDownItem
---------------------

This dropdown item type renders a simple element.
Use this element if you need a link, button.

..  code-block:: php

    // use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;

    $item = GeneralUtility::makeInstance(DropDownItem::class)
        ->setTag('a')
        ->setHref('#')
        ->setLabel('Label')
        ->setTitle('Title')
        ->setIcon($this->iconFactory->getIcon('actions-heart'))
        ->setAttributes(['data-value' => '123']);
    $dropDownButton->addItem($item);


DropDown\DropDownRadio
----------------------

This dropdown item type renders an element with an active state.
Use this element to display a radio-like selection of a state.
When set to active, it will show a dot in front of the icon and
text to indicate that this is the current selection.

At least 2 of these items need to exist within a dropdown button,
so a user has a choice of a state to select.

Example: Viewmode -> List / Tiles

..  code-block:: php

    // use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;

    $item = GeneralUtility::makeInstance(DropDownRadio::class)
        ->setHref('#')
        ->setActive(true)
        ->setLabel('List')
        ->setTitle('List')
        ->setIcon($this->iconFactory->getIcon('actions-viewmode-list'))
        ->setAttributes(['data-type' => 'list']);
    $dropDownButton->addItem($item);

    $item = GeneralUtility::makeInstance(DropDownRadio::class)
        ->setHref('#')
        ->setActive(false)
        ->setLabel('Tiles')
        ->setTitle('Tiles')
        ->setIcon($this->iconFactory->getIcon('actions-viewmode-tiles'))
        ->setAttributes(['data-type' => 'tiles']);
    $dropDownButton->addItem($item);


DropDown\DropDownToggle
-----------------------

This dropdown item type renders an element with an active state.
When set to active, it will show a checkmark in front of the icon
and text to indicate the current state.

..  code-block:: php

    // use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownToggle;

    $item = GeneralUtility::makeInstance(DropDownToggle::class)
        ->setHref('#')
        ->setActive(true)
        ->setLabel('Label')
        ->setTitle('Title')
        ->setIcon($this->iconFactory->getIcon('actions-heart'))
        ->setAttributes(['data-value' => '123']);
    $dropDownButton->addItem($item);


.. index:: Backend, ext:backend
