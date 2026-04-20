..  include:: /Includes.rst.txt

..  _feature-89334-1711526400:

=================================================
Feature: #89334 - Add generic TranslatorInterface
=================================================

See :issue:`89334`

Description
===========

A new :php:`\TYPO3\CMS\Core\Localization\TranslatorInterface` has been
introduced to provide a clean abstraction for translating labels in TYPO3.

The interface defines two methods:

*   :php:`translate()` translates a label using its identifier and domain,
    supporting argument interpolation, a default fallback value, and per-call
    locale overrides.
*   :php:`label()` resolves full TYPO3 label reference strings, for example
    `LLL:EXT:core/Resources/Private/Language/locallang.xlf:my.key`, and
    delegates to :php:`translate()`. This method serves as the interface-based
    equivalent of :php:`LanguageService::sL()`. The key differences are that
    it returns :php:`null` when a label cannot be resolved instead of an empty
    string, and supports argument interpolation, default values, and locale
    overrides.

:php-short:`\TYPO3\CMS\Core\Localization\LanguageService` now implements this
interface, making it possible to type-hint against the interface instead of the
concrete class.

Example usage:

..  code-block:: php
    :caption: Using TranslatorInterface via dependency injection

    use TYPO3\CMS\Core\Localization\TranslatorInterface;

    final class MyController
    {
        public function __construct(
            private readonly TranslatorInterface $translator,
        ) {}

        public function someAction(): void
        {
            // Translate by identifier and domain
            $label = $this->translator->translate(
                'button.save',
                'my_extension.messages',
            );

            // Translate by identifier and filename (discouraged)
            $label = $this->translator->translate(
                'button.save',
                'EXT:my_extension/Resources/Private/Language/locallang.xlf',
            );

            // Translate with arguments
            $label = $this->translator->translate(
                'record.count',
                'my_extension.messages',
                [5],
            );

            // Translate with a default fallback
            $label = $this->translator->translate(
                'missing.key',
                'my_extension.messages',
                [],
                'Fallback text',
            );

            // Label reference with arguments and default
            $label = $this->translator->label(
                'my_extension.messages:record.count',
                [5],
                'Fallback text',
            );

            // Resolve a full label reference string with file and LLL prefix (discouraged)
            $label = $this->translator->label(
                'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:button.save',
            );
        }
    }

The :php:`label()` method accepts the following reference formats:

*   `my_extension.messages:my.key`
*   `EXT:my_extension/Resources/Private/Language/locallang.xlf:my.key`
*   `LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:my.key`

The `LLL:` prefix is optional and removed before resolution.

Impact
======

Extension developers can now type-hint against
:php-short:`\TYPO3\CMS\Core\Localization\TranslatorInterface` instead of the
concrete :php-short:`\TYPO3\CMS\Core\Localization\LanguageService` class. This
improves testability and decouples code from implementation.

The :php:`translate()` method of
:php-short:`\TYPO3\CMS\Core\Localization\LanguageService` has been extended
with two additional optional parameters:

*   :php:`$default` a fallback value returned when the label is not found
*   :php:`$locale` allows overriding the locale on a per-call basis

..  index:: PHP-API, ext:core
