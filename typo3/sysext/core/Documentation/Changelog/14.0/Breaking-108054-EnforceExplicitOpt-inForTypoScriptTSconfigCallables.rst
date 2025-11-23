..  include:: /Includes.rst.txt

..  _breaking-108054-1762881326:

=============================================================================
Breaking: #108054 - Enforce explicit opt-in for TypoScript/TSconfig callables
=============================================================================

See :issue:`108054`

Description
===========

To strengthen TYPO3's security posture and implement defense-in-depth
principles, a new hardening mechanism has been introduced that requires
explicit opt-in for methods and functions that can be invoked through
TypoScript configuration.

The new PHP attribute
:php:`#[\TYPO3\CMS\Core\Attribute\AsAllowedCallable]` must be applied to
any method that should be callable via:

*   TypoScript :typoscript:`userFunc` processing (including the
    :typoscript:`USER` and :typoscript:`USER_INT` content objects)
*   TypoScript :typoscript:`stdWrap` functions
    :typoscript:`preUserFuncInt`, :typoscript:`postUserFunc` and
    :typoscript:`postUserFuncInt`
*   TypoScript constant comment user functions
*   TSconfig :typoscript:`renderFunc` in suggest wizard configuration

This security enhancement implements strong defaults through explicit
configuration, following the principle of least privilege.

Implementation details:

*   New :php:`\TYPO3\CMS\Core\Attribute\AsAllowedCallable` PHP attribute
*   New :php:`\TYPO3\CMS\Core\Security\AllowedCallableAssertion` service
    for validation
*   Enhanced
    :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction()`
*   Enhanced
    :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::callUserFunction()`

Impact
======

Extension code that provides custom processing methods callable from
TypoScript or TSconfig will fail with a
:php:`\TYPO3\CMS\Core\Security\AllowedCallableException` if the target
method is not explicitly marked with the :php:`#[AsAllowedCallable]`
attribute.

The error message will be:

..  code-block:: text

    Attribute TYPO3\CMS\Core\Attribute\AsAllowedCallable required for
    callback reference: ["VendorName\\ExtensionName\\ClassName","methodName"]

Affected installations
======================

Scenarios using:

*   custom processing via TypoScript `userFunc`
*   custom processing via TypoScript constant comments
*   custom suggest wizard rendering via TSconfig `renderFunc`

Migration
=========

Add the :php:`#[AsAllowedCallable]` attribute to all methods that should
be callable from TypoScript or TSconfig.

**TypoScript userFunc example:**

..  code-block:: php
    :caption: EXT:my_extension/Classes/UserFunc/CustomProcessor.php

    use TYPO3\CMS\Core\Attribute\AsAllowedCallable;

    class CustomProcessor
    {
        #[AsAllowedCallable]
        public function process(
            string $content,
            array $conf
        ): string {
            return $content;
        }
    }

The attribute may be applied to:

*   public instance methods
*   public static methods
*   public :php:`__invoke()` methods
*   custom functions in the global namespace

Native PHP functions in the global namespace must be wrapped explicitly.

**Example for custom functions in the global namespace:**

..  code-block:: php

    namespace {
        use TYPO3\CMS\Core\Attribute\AsAllowedCallable;

        #[AsAllowedCallable]
        function customGlobalUserFunction(): string
        {
            return '...';
        }

        #[AsAllowedCallable]
        function nativePhpHashWrapper(
            string $algo,
            string $data,
            bool $binary = false
        ): string {
            return \hash($algo, $data, $binary);
        }
    }

..  index:: TSConfig, TypoScript, NotScanned, ext:core
