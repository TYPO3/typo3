===========================================================
Deprecation: #72859 - Deprecate methods of DocumentTemplate
===========================================================

Description
===========

The following methods within ``DocumentTemplate`` have been marked as deprecated:

* viewPageIcon()
* getHeader()
* getResourceHeader()
* header()
* icons()
* t3Button()
* wrapScriptTags()
* loadJavascriptLib()
* getContextMenuCode()

The following property within ``DocumentTemplate`` has been marked as deprecated:

* sectionFlag (is internal)


Impact
======

Calling any of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any installation with a third-party extension calling one of the methods in its PHP code.


Migration
=========

Instead of ``wrapScriptTags()`` use ``GeneralUtility::wrapJS``.

Instead of ``getContextMenuCode()`` use:

.. code-block:: php

    $this->getPageRenderer()->loadJquery();
    $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
