..  include:: /Includes.rst.txt

..  _breaking-107438-1736592000:

============================================================================
Breaking: #107438 - Default parseFunc configuration for Fluid Styled Content
============================================================================

See :issue:`107438`

Description
===========

Since TYPO3 v13, the basic :typoscript:`lib.parseFunc` and
:typoscript:`lib.parseFunc_RTE` configurations are always available through
:file:`EXT:frontend/ext_localconf.php`.

The default parseFunc configuration previously provided by
EXT:fluid_styled_content has been removed to avoid duplicated and outdated
settings. This unifies the parseFunc behavior between EXT:frontend and
EXT:fluid_styled_content.

The :typoscript:`allowTags` syntax is no longer set by default, as HTML
sanitization has been handled properly by the HTML Sanitizer for some time.
All HTML tags are now allowed by default in frontend output, while the
HTML Sanitizer controls which tags and attributes are ultimately permitted.

Note that the :typoscript:`allowTags` directive itself has **not** been
removed. It can still be set to restrict frontend output where desired.

The conceptual approach is:

*   The backend (RTE and its HTML parser/processing) already cleans unwanted
    content and controls what is stored in the database.
*   The frontend output (parseFunc) should only add additional restrictions
    through :typoscript:`allowTags` when content comes from external or
    untrusted sources (for example, custom Extbase output).

Impact
======

Custom TypoScript configurations using :typoscript:`allowTags` syntax may no
longer work as expected. Specifically:

*   :typoscript:`allowTags := addToList(wbr)` no longer appends `wbr`.
    Instead, it limits allowed tags to only `wbr`.
*   Default CSS classes on HTML elements (for example
    :html:`<table class="contenttable">`) are no longer automatically added by
    the parseFunc configuration.
*   The parseFunc configuration in EXT:fluid_styled_content no longer
    provides custom link-handling options such as `extTarget` and `keep`.

Affected installations
======================

TYPO3 installations that use:

*   Custom TypoScript configurations relying on prior default
    :typoscript:`allowTags` behavior.
*   Extensions or sites depending on the specific parseFunc configuration
    previously provided by EXT:fluid_styled_content.
*   Configurations expecting automatic CSS class additions to HTML elements.
*   Sites relying on the old external link-handling behavior from the
    Fluid Styled Content parseFunc.

Migration
=========

If you need to allow specific HTML tags, explicitly configure
:typoscript:`allowTags` instead of extending a former default.

**Before (no longer works):**

..  code-block:: typoscript

    lib.parseFunc_RTE.allowTags := addToList(wbr)

**After:**

..  code-block:: typoscript

    lib.parseFunc_RTE.allowTags = b,span,i,em,wbr,...

For custom CSS classes on HTML elements, use custom CSS or add them through
Fluid templates or TypoScript processing instead.

If you require the previous link-handling behavior, configure it explicitly:

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
