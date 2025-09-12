..  include:: /Includes.rst.txt

..  _breaking-107438-1736592000:

============================================================================
Breaking: #107438 - Default parseFunc configuration for Fluid Styled Content
============================================================================

See :issue:`107438`

Description
===========

Since TYPO3 v13, the most basic `lib.parseFunc` and `lib.parseFunc_RTE`
configuration is available at any time in :file:`EXT:frontend/ext_localconf.php`.

The default parseFunc configuration that was previously provided by
`EXT:fluid_styled_content` has been removed to avoid duplicate settings
and outdated configurations. This unifies the parseFunc behavior across
both EXT:frontend and EXT:fluid_styled_content.

The `allowTags` syntax is no longer set by default, as HTML sanitization
is handled by the htmlSanitizer properly for some time. All HTML tags are
now allowed by default to appear in frontend output, with the htmlSanitizer
controlling which tags and attributes are permitted.

Note that the `allowTags` directive itself is not removed. It can still be
set to restrict frontend output.

The conceptual idea is: The backend (RTE and its htmlParser/Processing) performs
scrubbing of unwanted content already, and controls what is stored as content. The
frontend output (parseFunc) should only further restrict output via `allowTags` in
cases where the content might come from outside sources (or maybe Extbase frontend handling).

Impact
======

Custom TypoScript configurations using `allowTags` syntax will no longer
work as expected. Specifically:

- `allowTags := addToList(wbr)` will not properly add the `wbr` tag anymore,
  but instead would restrict allowed tags to ONLY `wbr`.
- Default CSS classes on HTML elements (like `<table class="contenttable">`)
  are no longer automatically added by parseFunc configuration
- The parseFunc configuration in EXT:fluid_styled_content no longer provides
  custom link handling options like `extTarget` and `keep` parameters

Affected installations
======================

TYPO3 installations that use:

- Custom TypoScript configurations with `allowTags` syntax that relies on the
  former default configuration
- Extensions or sites relying on the **specific** parseFunc configuration
  previously provided by EXT:fluid_styled_content
- Custom configurations expecting automatic CSS class additions to HTML elements
- Sites depending on specific external link handling behavior from FSC parseFunc


Migration
=========

If you need to allow specific HTML tags, fully configure the `allowTags`
option without relying on prior default configuration:

**Before (no longer working):**

..  code-block:: typoscript

    lib.parseFunc_RTE.allowTags := addToList(wbr)

**After:**

..  code-block:: typoscript

    lib.parseFunc_RTE.allowTags = b,span,i,em,wbr...

For custom CSS classes on HTML elements, use CSS or add the classes
through other means like Fluid templates or custom TypoScript processing.

If you require the previous link handling behavior, you need to explicitly
configure the parseFunc settings:

..  code-block:: typoscript

    lib.parseFunc_RTE {
        makelinks {
            http {
                extTarget = _blank
                keep = path
            }
        }
    }

..  index:: TypoScript, ext:fluid_styled_content, ext:frontend, NotScanned
