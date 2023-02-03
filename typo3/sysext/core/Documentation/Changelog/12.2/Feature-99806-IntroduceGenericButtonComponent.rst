.. include:: /Includes.rst.txt

.. _feature-99806-1675673144:

===================================================
Feature: #99806 - Introduce GenericButton component
===================================================

See :issue:`99806`

Description
===========

A new component :php:`TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton`
is introduced that allows to render any markup in the module menu bar.

Example:

..  code-block:: php

    $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
    $genericButton = GeneralUtility::makeInstance(GenericButton::class)
        ->setTag('a')
        ->setHref('#')
        ->setLabel('Label')
        ->setTitle('Title')
        ->setIcon($this->iconFactory->getIcon('actions-heart'))
        ->setAttributes(['data-value' => '123']);
    $buttonBar->addButton($genericButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);


.. index:: Backend, ext:backend
