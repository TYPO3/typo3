.. include:: /Includes.rst.txt

========================================================
Feature: #84120 - Absolute URLs for typolink ViewHelpers
========================================================

See :issue:`84120`

Description
===========

The new parameter `absolute` has been added to the Fluid ViewHelpers `<f:uri.typolink>` and `<f:link.typolink>`,
allowing to generate absolute links, like other ViewHelpers used for linking handle it already.


Impact
======

It is now possible to add the `absolute` parameter to the ViewHelpers above.

.. code-block:: html

    <f:link.typolink parameter="23" absolute="true">Link To My Page</f:link.typolink>
    <f:uri.typolink parameter="23" absolute="true" />

generates

.. code-block:: html

    <a href="https://www.mydomain.com/index.php?id=23">Link to My Page</a>
    https://www.mydomain.com/index.php?id=23

.. index:: Fluid, ext:fluid
