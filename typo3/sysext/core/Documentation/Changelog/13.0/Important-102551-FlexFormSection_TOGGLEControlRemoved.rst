.. include:: /Includes.rst.txt

.. _important-102551-1701252243:

===============================================================
Important: #102551 - FlexForm section `_TOGGLE` control removed
===============================================================

See :issue:`102551`

Description
===========

Historically, FlexForms store their section collapsing state within the flex
structure in the database, having the impact that the state is reflected to
every backend user. The control field `_TOGGLE` responsible for this behavior
is now removed, the state is persisted in the backend user's local storage
instead.

It is highly recommended to clean existing FlexForm records by invoking the
following command:

..  code-block:: bash

    bin/typo3 cleanup:flexforms

If this is not possible, a scheduler task of type
:guilabel:`Execute console commands` with the command
:guilabel:`cleanup:flexforms: Clean up database FlexForm fields that do not match the chosen data structure.`
may be set up and used.

.. index:: FlexForm, ext:backend
