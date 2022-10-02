.. include:: /Includes.rst.txt

.. _breaking-97927-1657730964:

=================================================================
Breaking: #97927 - Removed TypoScript option config.doctypeSwitch
=================================================================

See :issue:`97927`

Description
===========

Previous TYPO3 versions allowed to set :typoscript:`config.doctypeSwitch`
via TypoScript.

If this option was set, the order of <?xml...> and <!DOCTYPE...> during the
rendering of a Frontend page was reversed. This was needed in the past for
Internet Explorer to be standards-compliant with XHTML. Otherwise IE's
"Quirks Mode" was used.

Nowadays, usages for both Internet Explorer (which is not supported anymore) and
XHTML have been low, which is why the option is now removed from TYPO3 Core.

Impact
======

Setting the option :typoscript:`config.doctypeSwitch` has no effect anymore, the
XML declaration and doctype statement are kept as is.

Affected installations
======================

TYPO3 installations with old templates having this TypoScript option set.

Migration
=========

It is recommended to avoid using this functionality, and to switch to HTML5.

.. index:: TypoScript, NotScanned, ext:frontend
