..  include:: /Includes.rst.txt

..  _feature-93334-1729000000:

============================================
Feature: #93334 - Translation Domain Mapping
============================================

See :issue:`93334`

Description
===========

Translation domains have been introduced as a shorter alternative to
file-based references for label resources (:file:`.xlf` XLIFF files). The
syntax uses the format `package[.subdomain...].resource` and is fully backward
compatible with existing `LLL:EXT:` references. *Package* refers to the
extension key, such as "*backend*" for "EXT:backend".

This syntax is designed to improve readability, remove explicit references to
file extensions, and provide convenience for new developers and integrators.
The previous :file:`locallang.xlf` convention has been replaced with a more
generic "*messages*" resource name, following common conventions in other
localization systems (for example Symfony). This is also where the term
*translation domain* originates.

Example:

..  code-block:: php

    // Domain-based reference
    $languageService->sL('backend.toolbar:save');

    // Equivalent file-based reference (still supported)
    $languageService->sL(
        'LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:save'
    );

..  note::

    The existing syntax and naming
    (`LLL:EXT:extension/Resources/Private/Language/locallang.xlf:label`)
    will remain available without deprecation for a long time.

..  _feature-93334-translation-domain-format:

Translation Domain Format
=========================

The format defines two parts: the *package part* (extension key) and the
*resource part*, separated by a dot.

The resource part omits historical namings such as `locallang.xlf` and the
`locallang_` prefix. The actual label identifier is separated by a colon.

Format
------

..  code-block:: php
    :caption: Example usage of "package.resource:identifier"

    $languageService->sL('backend.toolbar:save');
    // Resolves to: EXT:backend/Resources/Private/Language/locallang_toolbar.xlf
    // and returns the translated "save" identifier.

..  _feature-93334-domain-resolution:

Domain Resolution
=================

..  _feature-93334-deterministic-mapping:

Deterministic File-Based Mapping
--------------------------------

Translation domains are resolved deterministically by scanning the file
system. When a domain is first requested for a package:

1. All label files in :directory:`Resources/Private/Language/` are discovered.
2. A domain name is generated from each file name.
3. The domain-to-file mapping is cached in `cache.l10n`.
4. Subsequent requests use the cached mapping.

This ensures that domain names always correspond to existing files and avoids
speculative file system lookups.

When there are filename conflicts such as :file:`locallang_db.xlf` and
:file:`db.xlf`, then :file:`locallang_db.xlf` will be ignored.

..  _feature-93334-performance:

Performance Characteristics
---------------------------

The implementation reduces file system operations compared to traditional
file-based lookups, as all label files within an extension are discovered once.

..  _feature-93334-domain-rules:

Domain Generation Rules
-----------------------

Domain names are generated from file paths using these transformation rules:

1. The base path :directory:`Resources/Private/Language/` is omitted.

2. Standard filename patterns:

   * :file:`locallang.xlf` → `.messages`
   * :file:`locallang_toolbar.xlf` → `.toolbar`
   * :file:`locallang_sudo_mode.xlf` → `.sudo_mode`

3. Subdirectories use dot notation:

   * :file:`Form/locallang_tabs.xlf` → `.form.tabs`

4. Site Set labels receive the `.sets` prefix:

   * :file:`Configuration/Sets/Felogin/labels.xlf` → `.sets.felogin`

5. Case conversion:

   * UpperCamelCase → snake_case (`SudoMode` → `sudo_mode`)
   * snake_case → preserved (`sudo_mode` → `sudo_mode`)

6. Locale prefixes are ignored for domain name generation but properly
   evaluated for locale-specific translations:

   * (`de.locallang.xlf` → `messages`)
   * (`de-AT.tabs.xlf` → `tabs`)

Examples:

..  code-block:: text

    File Path                                     → Domain
    ────────────────────────────────────────────────────────────
    EXT:backend/.../locallang.xlf                 → backend.messages
    EXT:backend/.../locallang_toolbar.xlf         → backend.toolbar
    EXT:core/.../Form/locallang_tabs.xlf          → core.form.tabs
    EXT:felogin/Configuration/Sets/.../labels.xlf → felogin.sets.felogin

..  important::

    The domain name `messages` is currently evaluated for both the
    legacy file name :file:`locallang.xlf` but also for new files :file:`messages.xlf`.
    If a file `messages.xlf` is present, this means the `locallang.xlf`
    will never be automatically evaluated for the resulting `messages` domain.

    It is recommended to avoid having both files in the same directory, unless
    both contain the same label contents, as no merging of these two
    files is performed.

..  _feature-93334-usage:

Usage
=====

The translation domain system integrates with the existing
:php:`\TYPO3\CMS\Core\Localization\LanguageService` API. Both domain-based and
file-based references are supported:

..  code-block:: php

    use TYPO3\CMS\Core\Localization\LanguageService;

    $languageService = $this->languageServiceFactory->createFromSiteLanguage(
        $request->getAttribute('language')
    );

    // Domain-based reference
    $label = $languageService->sL('backend.toolbar:menu.item');

    // Another domain-based reference
    $label = $languageService->sL('backend.messages:button.save');

    // Traditional file reference (still supported)
    $label = $languageService->sL(
        'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:button.save'
    );

Domain-based references are shorter and reveal less implementation detail than
full file paths.

..  _feature-93334-cli:

CLI Command
===========

The development command :bash:`bin/typo3 language:domain:list` lists all available
translation domains along with their available translations and label counts:

..  code-block:: bash

    # List domains in active extensions
    php bin/typo3 language:domain:list

    # Filter by extension
    php bin/typo3 language:domain:list --extension=backend

Output:

..  code-block:: text

    +--------------------+---------------------------------------+----------+
    | Translation Domain | Label Resource                        | # Labels |
    +--------------------+---------------------------------------+----------+
    | backend.messages   | EXT:backend/.../locallang.xlf         | 84       |
    | backend.toolbar    | EXT:backend/.../locallang_toolbar.xlf | 42       |
    +--------------------+---------------------------------------+----------+

The **Labels** column displays the number of translatable labels within the
English source file.

On top of this, the development command :bash:`bin/typo3 language:domain:search`
can be used to search for specific label contents. Both commands are provided
in the `EXT:lowlevel` extension.

..  _feature-93334-psr14:

PSR-14 Event
============

The event :php:`\TYPO3\CMS\Core\Localization\Event\BeforeLabelResourceResolvedEvent`
is dispatched after domain generation, allowing customization of domain names.

The event provides these public properties:

*   :php:`$packageKey` — The extension key (read-only).
*   :php:`$domains` — An associative array mapping domain names to label files
    (modifiable): :php:`array<string, string>`.

Example
-------

Event listener implementation:

..  code-block:: php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Localization\Event\BeforeLabelResourceResolvedEvent;

    final readonly class CustomTranslationDomainResolver
    {
        #[AsEventListener(identifier: 'my-extension/custom-domain-names')]
        public function __invoke(BeforeLabelResourceResolvedEvent $event): void
        {
            if ($event->packageKey !== 'my_extension') {
                return;
            }

            // Use file my_messages.xlf even if locallang.xlf is found
            $event->domains['my_extension.messages'] =
                'EXT:my_extension/Resources/Private/Language/my_messages.xlf';
        }
    }

Backend modules
===============

Previously, backend module labels (including their title and description) were
defined in a file like this:

..  code-block:: xml
    :caption: EXT:my_extension/Resources/Private/Language/locallang_mod.xlf
    :emphasize-lines: 6,9,12

    <?xml version="1.0" encoding="UTF-8"?>
    <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
        <file source-language="en" datatype="plaintext" original="EXT:my_extension/Resources/Private/Language/locallang_mod.xlf" date="2038-10-28T13:37:37Z" product-name="mymodule">
            <header/>
            <body>
                <trans-unit id="mlang_labels_tablabel">
                    <source>My module</source>
                </trans-unit>
                <trans-unit id="mlang_labels_tabdescr">
                    <source>Shows my module.</source>
                </trans-unit>
                <trans-unit id="mlang_tabs_tab">
                    <source>My label</source>
                </trans-unit>
            </body>
        </file>
    </xliff>

and utilized via the module definition:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Modules.php
    :emphasize-lines: 9

    <?php
    return [
        'my_module' => [
            'parent' => 'web',
            'position' => ['after' => 'web_list'],
            'access' => 'user',
            'path' => '/module/my-module',
            'iconIdentifier' => 'my-module-icon',
            'labels' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang_mod.xlf',
            'aliases' => ['web_MyModule'],
            'routes' => [
                '_default' => [
                    'target' => MyController::class . '::handleRequest',
                ],
            ],
        ],
    ];

Now, labels can use more speaking identifiers:

..  code-block:: xml
    :caption: EXT:my_extension/Resources/Private/Language/Module/mymodule.xlf

    <?xml version="1.0" encoding="UTF-8"?>
    <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
        <file source-language="en" datatype="plaintext" original="EXT:my_extension/Resources/Private/Language/Modules/mymodule.xlf" date="2026-11-05T16:22:37Z" product-name="mymodule">
            <header/>
            <body>
                <trans-unit id="short_description">
                    <source>My module</source>
                </trans-unit>
                <trans-unit id="description">
                    <source>Shows my module.</source>
                </trans-unit>
                <trans-unit id="title">
                    <source>My label</source>
                </trans-unit>
            </body>
        </file>
    </xliff>

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Modules.php
    :emphasize-lines: 10

    <?php
    return [
        'my_module' => [
            'parent' => 'web',
            'position' => ['after' => 'web_list'],
            'access' => 'user',
            'path' => '/module/my-module',
            'iconIdentifier' => 'my-module-icon',
            'labels' => 'my_extension.modules.my_module',
            'aliases' => ['web_MyModule'],
            'routes' => [
                '_default' => [
                    'target' => MyController::class . '::handleRequest',
                ],
            ],
        ],
    ];

The naming for the short-hand translation domain for modules should follow
the following pattern as best practice:

*  `<extensionkey>.modules.<modulename>` - **when multiple modules exist for an extension**.
   Both `extensionKey` and `modulename` should use lower snake case ("some_long_module_name"),
   ideally without underscores (`qrcode.modules.generator` is more readable than
   `qrcode.modules.backend_image_generator` for example). Files are put into
   :file:`EXT:extensionkey/Resources/Private/Languages/Modules/modulename.xlf`.
*  `<extensionkey>.module` - **single backend module only**
   The file is saved as :file:`EXT:extensionkey/Resources/Private/Languages/module.xlf`.

To summarize, the key changes are:

#.  Use a speaking XLIFF file inside :directory:`/Resources/Private/Languages/Modules` (best practice, could be any sub-directory)
#.  Use understandable XLIFF identifiers:
    - "title" instead of "mlang_tabs_tab"
    - "short_description" instead of "mlang_labels_tablabel"
    - "description" instead of "mlang_labels_tabdescr"
#.  Use short-form identifiers ("my_extension.modules.my_module" instead of "LLL:EXT:my_extension/Resources/Private/Language/locallang_mod.xlf")
    inside the :file:`Backend/Modules.php` registration.

All TYPO3 Core backend modules that used the old label identifiers have been migrated to the new syntax, the utilized
files are now deprecated, see :ref:`deprecation <deprecation-107938-1762181263>`. TYPO3 Core also uses
singular module language containers like `workspaces.module` instead of `workspaces.modules.workspaces`.

Impact
======

Translation domains provide a shorter, more readable alternative to file-based
label references. The implementation uses deterministic file-system scanning
with per-package caching to reduce lookups.

All existing `LLL:EXT:` file references continue to work. Translation domains
are optional and can be adopted incrementally. Both syntaxes can coexist in
the same codebase. This affects TypoScript, Fluid
:fluid:`<f:translate>` usages, TCA configuration, and PHP code using the
:php-short:`\TYPO3\CMS\Core\Localization\LanguageService` API.

TYPO3 Core will gradually migrate internal references to translation domains
over time, increasing readability—especially in Fluid templates or TCA
definitions.

Technical components:

:php:`\TYPO3\CMS\Core\Localization\TranslationDomainMapper`
    Maps domains to file paths and manages the cache.
:php:`\TYPO3\CMS\Core\Localization\LabelFileResolver`
    Discovers label files and handles locale resolution.
:php:`\TYPO3\CMS\Core\Localization\LocalizationFactory`
    Integrates domain resolution transparently.

The :php-short:`\TYPO3\CMS\Core\Localization\TranslationDomainMapper`
automatically detects `EXT:` file references and passes them through unchanged.

..  index:: PHP-API, Localization, ext:core
