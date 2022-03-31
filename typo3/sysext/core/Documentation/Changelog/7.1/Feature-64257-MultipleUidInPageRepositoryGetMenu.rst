
.. include:: /Includes.rst.txt

===================================================================
Feature: #64257 - Support multiple UID in PageRepository::getMenu()
===================================================================

See :issue:`64257`

Description
===========

An array of `uid` values can be passed to
`PageRepository::getMenu()`, providing the chance to build menus
from several roots.

Example: Fetch children of pages #2 and #3:

.. code-block:: php

    $pageRepository = new \TYPO3\CMS\Frontend\Page\PageRepository();
    $pageRepository->init(false);
    $rows = $pageRepository->getMenu(array(2, 3));


.. index:: PHP-API, Frontend
