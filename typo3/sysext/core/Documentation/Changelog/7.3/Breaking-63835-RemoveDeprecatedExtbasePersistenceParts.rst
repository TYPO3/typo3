
.. include:: ../../Includes.txt

=======================================================================
Breaking: #63835 - Remove Deprecated Parts in Extbase Persistence Layer
=======================================================================

See :issue:`63835`

Description
===========

The previously deprecated functions `TYPO3\CMS\Extbase\Persistence\Generic\Backend->setDeletedObjects()` and
`TYPO3\CMS\Extbase\Persistence\Repository->replace()` inside the Persistence Layer of Extbase have been removed.
The protected property "session" inside `TYPO3\CMS\Extbase\Persistence\Repository` has been removed as well.


Impact
======

Any direct calls to the methods will now exit with a PHP Fatal Error.


.. index:: PHP-API, ext:extbase
