..  include:: /Includes.rst.txt

..  _feature-100254-1742119200:

==================================================================
Feature: #100254 - Support download attribute in file link browser
==================================================================

See :issue:`100254`

Description
===========

The HTML5 :html:`download` attribute can now be configured for a link in
the file link browser. When set, the browser forces a file download
instead of navigating to the file URL.

The link browser renders a `Force download` checkbox for file links. When
enabled, an optional `Custom filename` text field appears, allowing
editors to specify an alternative filename for the downloaded file.

The TypoLink codec supports an optional seventh TypoLink segment for
:html:`download`. The value :php:`true` produces a boolean download
attribute (:html:`<a download>`). Any other string value produces a
named download attribute (:html:`<a download="custom-name.pdf">`).

Example TypoLink strings:

*   `t3://file?uid=42 - - - - - true`
*   `t3://file?uid=42 - - - - - report.pdf`

Impact
======

Editors can now set whether a file should be downloaded or
displayed in the browser directly in the link. This works in both the RTE and non-RTE link
browser dialogs.

Existing TypoLink values without :html:`download` remain unchanged and
continue to work as before.

..  index:: Backend, Frontend, RTE, ext:backend, ext:frontend
