.. include:: /Includes.rst.txt

.. _changelog-Feature-93048-IntroduceBackendURLRewrites:

================================================
Feature: #93048 - Introduce Backend URL rewrites
================================================

See :issue:`93048`

Description
===========

The TYPO3 backend does now feature URL rewrites which allows
the use of human readable urls. This will enable TYPO3
to introduce deep-linking functionality in the future. By
that, it will be possible to share URLs, which directly link
to a specific module or even a specific record, in the backend.

Example
-------

.. code-block:: none

   // Before
   https://example.com/typo3/index.php?route=%2Fmain

   // After
   https://example.com/typo3/main


This feature is enabled by default and will work as soon as
the necessary rewrite rule is added in the webserver configuration.
See: :ref:`changelog-Breaking-93048-BackendURLRewrites` for more
details about this.

To generate human readable urls for custom backend modules and routes,
extension authors can use the public :php:`UriBuilder` API.


Impact
======

TYPO3 now builds human readable urls for the backend by default.
Extension authors also automatically benefit form this when
using the public :php:`UriBuilder` API.


Related
=======

- :ref:`changelog-Breaking-93048-BackendURLRewrites`

.. index:: Backend, ext:backend
