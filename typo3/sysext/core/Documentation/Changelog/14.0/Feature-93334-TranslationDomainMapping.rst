..  include:: /Includes.rst.txt

..  _feature-93334-1729000000:

============================================
Feature: #93334 - Translation Domain Mapping
============================================

See :issue:`93334`

Description
===========

Translation domains have been introduced as a shorter alternative to file-based
references for label resources (:file:`.xlf` XLIFF files). The syntax uses the format
`package[.subdomain...].resource` and is fully backward compatible
with existing `LLL:EXT:` references. "Package" refers to the *extension key*,
like "*backend*" for "EXT:backend".

This synax is designed to improve readability, remove a clear reference
to the used file extension and to add convenience for new developers and
integrators: The previous :file:`locallang.xlf` convention as well as the
name has been removed in favor of a more generic "*messages*" resource name,
which is common for localization systems or Symfony-based applications.
This is also where the term "translation domain" stems from.

Example:

.. code-block:: php

    // Domain-based reference
    $languageService->sL('backend.toolbar:save');

    // Equivalent file-based reference (existing syntax, still supported)
    $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_toolbar.xlf:save');

..  note::

    The existing syntax and naming
    (`LLL:EXT:extension/Resources/Private/Language/locallang.xlf:label`)
    will be around for a long while without a deprecation notice.

..  _feature-93334-translation-domain-format:

Translation Domain Format
=========================

The format defines two parts: The *package part* (extension key) and the *resource part*.
These are separated by a dot.

As mentioned, the resource part will leave out previous historical
namings, especially `locallang.xlf` and the `locallang_` prefix.

The actual identifier within the resource is separated by a colon.

Format
------

.. code-block:: php
   :caption: Example usage of "package.resource:identifier"

    $languageService->sL('backend.toolbar:save');
    // Resolves to: EXT:backend/Resources/Private/Language/locallang_toolbar.xlf and
    // returns the translated "save" identifier.

..  _feature-93334-domain-resolution:

Domain Resolution
=================

..  _feature-93334-deterministic-mapping:

Deterministic File-Based Mapping
---------------------------------

Translation domains are resolved deterministically by scanning the file system.
When a domain is first requested for a package:

1. All label files in :directory:`Resources/Private/Language/` are discovered
2. A domain name is generated from each file name
3. The domain-to-file mapping is cached in `cache.l10n`
4. Subsequent requests use the cached mapping

This ensures domain names always correspond to existing files and prevents
speculative file system lookups.

When there are filename conflicts, such as :file:`locallang_db.xlf` and :file:`db.xlf`,
then :file:`locallang_db.xlf` will be ignored.


..  _feature-93334-performance:

Performance Characteristics
---------------------------

The implementation reduces file system operations compared to traditional
file-based lookups, as all label files within an extension are discovered
once.

..  _feature-93334-domain-rules:

Domain Generation Rules
-----------------------

Domain names are generated from file paths using these transformation rules:

1. The base path :directory:`Resources/Private/Language/` is omitted

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

6. Locale prefixes are irrelevant for the resource identifier resolving.
   These prefixes will be properly evaluated internally for later locale-based translations:

   * (`de.locallang.xlf` → `messages`)
   * (`de-AT.tabs.xlf` → `tabs`)

Examples:

.. code-block:: text

    File Path                                     → Domain
    ────────────────────────────────────────────────────────────
    EXT:backend/.../locallang.xlf                 → backend.messages
    EXT:backend/.../locallang_toolbar.xlf         → backend.toolbar
    EXT:core/.../Form/locallang_tabs.xlf          → core.form.tabs
    EXT:felogin/Configuration/Sets/.../labels.xlf → felogin.sets.felogin

..  _feature-93334-usage:

Usage
=====

The translation domain system integrates with the existing
:php:`TYPO3\CMS\Core\Localization\LanguageService` API. Both
domain-based and file-based references are supported:

.. code-block:: php

    $languageService = $this->languageServiceFactory->createFromSiteLanguage(
        $request->getAttribute('language')
    );

    // Domain-based reference
    $label = $languageService->sL('backend.toolbar:menu.item');

    // Another domain-based reference
    $label = $languageService->sL('backend.messages:button.save');

    // Traditional file reference (still supported)
    $label = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:button.save');

Domain-based references are shorter and expose less implementation detail
compared to full file paths.

..  _feature-93334-cli:

CLI Command
===========

The command :bash:`bin/typo3 language:domain:list` lists all available translation domains
with their available translations and label counts:

.. code-block:: bash

    # List domains in active extensions
    php bin/typo3 language:domain:list

    # Filter by extension
    php bin/typo3 language:domain:list --extension=backend

Output:

.. code-block:: text

    +--------------------+---------------------------------------+----------+
    | Translation Domain | Label Resource                        | # Labels |
    +--------------------+---------------------------------------+----------+
    | backend.messages   | EXT:backend/.../locallang.xlf         | 84       |
    | backend.toolbar    | EXT:backend/.../locallang_toolbar.xlf | 42       |
    +--------------------+---------------------------------------+----------+

The **Labels** column displays the number of translatable labels within
the English source file.

..  _feature-93334-psr14:

PSR-14 Event
============

The event :php:`BeforeLabelResourceResolvedEvent` is dispatched after domain
generation, allowing customization of domain names.

Event: :php:`TYPO3\CMS\Core\Localization\Event\BeforeLabelResourceResolvedEvent`

The event provides these public properties:

*   :php:`$packageKey` — The extension key (read-only).
*   :php:`$domains` — An associative array mapping domain names to label files
    (modifiable): :php:`array<string, string>`.

Example
-------

Event listener implementation:

.. code-block:: php

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


Impact
======

Translation domains provide a shorter, more readable alternative to file-based
label references. The implementation uses deterministic file-system scanning
with per-package caching to reduce file system operations.

All existing `LLL:EXT:` file references continue to work. Translation domains
are optional and can be adopted incrementally. Both syntaxes can be mixed
within the same codebase. This affects TypoScript, Fluid :fluid:`<f:translate>`
usages, TCA configuration, and PHP code using the :php:`LanguageService` API.

TYPO3 Core will slowly migrate internal references to use translation domains
over time, as this increases readability, especially in Fluid templates,
or TCA references.

Technical components:

* :php:`TranslationDomainMapper` - Maps domains to file paths, manages cache
* :php:`LabelFileResolver` - Discovers label files and handles locale resolution
* :php:`LocalizationFactory` - Integrates domain resolution transparently

The :php:`TranslationDomainMapper` automatically detects file references
(`EXT:` prefix) and passes them through unchanged.

..  index:: PHP-API, Localization, ext:core
