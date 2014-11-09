===================================================
Deprecation: #62795 - DocumentTemplate->endPageJS()
===================================================

Description
===========

Method TYPO3\CMS\Backend\Template\DocumentTemplate::endPageJS() and the according property "endJS" is deprecated.


Impact
======

None, as it isn't in use anymore since TYPO3 CMS 4.5 and was responsible for notifying the browser that the session
is still active.


Affected installations
======================

Installations misusing top.busy until now for their own good will break.