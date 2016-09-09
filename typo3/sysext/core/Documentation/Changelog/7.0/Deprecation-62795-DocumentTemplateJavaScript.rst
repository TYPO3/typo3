
.. include:: ../../Includes.txt

===================================================
Deprecation: #62795 - DocumentTemplate->endPageJS()
===================================================

See :issue:`62795`

Description
===========

Method :code:`TYPO3\CMS\Backend\Template\DocumentTemplate::endPageJS()` and the according property :code:`endJS` have been marked as deprecated.


Impact
======

None, as it isn't in use anymore since TYPO3 CMS 4.5 and was responsible for notifying the browser that the session
is still active.


Affected installations
======================

Installations misusing top.busy until now for their own good will break.
