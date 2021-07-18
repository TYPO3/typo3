.. include:: ../../Includes.txt

=======================================================================
Breaking: #79270 - Removed RTE processing option disableUnifyLineBreaks
=======================================================================

See :issue:`79270`

Description
===========

The RTE option that could be set via PageTSconfig :typoscript:`RTE.default.proc.disableUnifyLineBreaks` has been removed.

The option was never set by default.

If activated, it allowed that both line feeds (LFs) and carriage returns (CRs) were left as is. If the option was not set,
all line breaks were converted to CRLFs after processing (Windows-syntax) to have a unified style of line breaks
in the database.

The option was only there for historic reasons in TYPO3 v3 and TYPO3 v4 to allow to simulate old behaviour
when no RTE was available.


Impact
======

When editing or saving a rich-text content element, all line breaks are converted to CRLFs at any time. If this option is set, it is not
evaluated anymore.


Affected Installations
======================

Any installation having the mentioned option explicitly activated in PageTSConfig and counting on the non-unified behaviour.


Migration
=========

Remove the option from TSconfig as it is not necessary anymore.

.. index:: RTE, TSConfig
