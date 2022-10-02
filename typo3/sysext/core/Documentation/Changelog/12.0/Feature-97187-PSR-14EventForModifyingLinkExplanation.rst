.. include:: /Includes.rst.txt

.. _feature-97187:

=============================================================
Feature: #97187 - PSR-14 event for modifying link explanation
=============================================================

See :issue:`97187`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Form\Event\ModifyLinkExplanationEvent`
has been introduced which serves as a more powerful and flexible alternative
for the now removed :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['linkHandler']`
hook.

While the removed hook effectively only allowed to modify the link explanation
of TCA `link` fields in case the resolved link type did not already match
one of those, implemented by TYPO3 itself, the new event now allows to
always modify the link explanation of any type. Additionally, this allows
to modify the `additionalAttributes`, displayed below the actual link
explanation field. This is especially useful for extended link handler setups.

To modify the link explanation, the following methods are available:

- :php:`getLinkExplanation()`: Returns the current link explanation data
- :php:`setLinkExplanation()`: Set the link explanation data
- :php:`getLinkExplanationValue()`: Returns a specific link explanation value
- :php:`setLinkExplanationValue()`: Sets a specific link explanation value

The link explanation array usually contains the following values:

- :php:`text` : The text to show in the link explanation field
- :php:`icon`: The markup for the icon, displayed in front of the link explanation field
- :php:`additionalAttributes`: The markup for additional attributes, displayed below the link explanation field

The current context can be evaluated using the following methods:

- :php:`getLinkData()`: Returns the resolved link data, such as the page uid
- :php:`getLinkParts()`: Returns the resolved link parts, such as `url`, `target` and `additionalParams`
- :php:`getElementData()`: Returns the full FormEngine `$data` array for the current element

Example
=======

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Backend\ModifyLinkExplanationEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/backend/modify-link-explanation'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Form\Event\ModifyLinkExplanationEvent;
    use TYPO3\CMS\Core\Imaging\Icon;
    use TYPO3\CMS\Core\Imaging\IconFactory;

    final class ModifyLinkExplanationEventListener
    {
        public function __construct(
            protected readonly IconFactory $iconFactory
        ) {
        }

        public function __invoke(
            ModifyLinkExplanationEvent $event
        ): void {
            // Use a custom icon for a custom link type
            if ($event->getLinkData()['type'] === 'myCustomLinkType') {
                $icon = $this->iconFactory->getIcon(
                    'my-custom-link-icon',
                    Icon::SIZE_SMALL
                )->render()
                $event->setLinkExplanationValue('icon', $icon);
            }
        }
    }

Impact
======

It's now possible to fully modify the link explanation of TCA `link`
elements, using the new PSR-14 event :php:`ModifyLinkExplanationEvent`.

.. index:: Backend, PHP-API, ext:backend
