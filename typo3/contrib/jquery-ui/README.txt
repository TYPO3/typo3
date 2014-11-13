This directory contains jQuery UI 1.11 components fetched from

	https://github.com/jquery/jquery-ui/tree/1-11-stable/ui/

In order to use each component only when used.

TYPO3 uses jQuery UI for various functionality, only used for
jQuery UI Core and jQuery UI Interactions.

All other functionality from the jQuery UI package is not in
scope for TYPO3 CMS. Other functionality (incl. components like
DatePicker, Spinner, Dialog, Button, Tabs, Tooltip) is done
via Twitter Bootstrap components and other alternatives.

Having separate files instead of one combined custom build
allows TYPO3 Backend Modules to only fetch the data needed
via RequireJS.

See the Core (e.g. FormEngine->Inline (jsfunc.inline.js) and
this document (http://learn.jquery.com/jquery-ui/environments/amd/)
for further details on how to implement jQuery UI.