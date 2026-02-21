..  include:: /Includes.rst.txt

..  _feature-108627-1736780000:

=====================================================================
Feature: #108627 - Allow adding inline language domains to JavaScript
=====================================================================

See :issue:`108627`

Description
===========

The new method :php:`PageRenderer->addInlineLanguageDomain()` allows loading
all labels from a language domain and making them available in JavaScript
via the :js:`TYPO3.lang` object.

The domain name follows the format `extension.domain` (e.g. `core.common`,
`core.modules.media`).
The language file is resolved automatically by the LanguageService, resolving
to files like :file:`EXT:core/Resources/Private/Language/locallang_common.xlf`
and :file:`EXT:core/Resources/Private/Language/Modules/media.xlf`.

See :ref:`translation domain syntax <feature-93334-translation-domain-format>`
for more details.

Labels are automatically prefixed with the domain name and accessible as
:js:`TYPO3.lang['domain:key']`, e.g. :js:`TYPO3.lang['core.common:notAvailableAbbreviation']`.

Example
=======

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    use TYPO3\CMS\Core\Page\PageRenderer;

    final class MyController
    {
        public function __construct(
            private readonly PageRenderer $pageRenderer,
        ) {}

        public function myAction(): void
        {
            // Load all labels from the 'myextension.frontend' domain
            $this->pageRenderer->addInlineLanguageDomain('myextension.frontend');
        }
    }

The labels are then available in JavaScript:

..  code-block:: javascript
    :caption: EXT:my_extension/Resources/Public/JavaScript/my-script.js

    // Access a label from the domain
    const label = TYPO3.lang['myextension.frontend:button.submit'];

Impact
======

Previously, it was possible to load entire language files using
:php:`addInlineLanguageLabelFile()` (which is still available), but labels
were added without any prefix.
This could lead to naming conflicts when multiple extensions used the same
label keys, potentially overriding each other's translations.

With the new domain-based approach, all labels are automatically prefixed
with the domain name (e.g. `myextension.frontend:label.key`). This provides
a unified, namespaced access pattern that eliminates the risk of collisions
between labels from different extensions or language files.


..  index:: JavaScript, PHP-API, ext:core
