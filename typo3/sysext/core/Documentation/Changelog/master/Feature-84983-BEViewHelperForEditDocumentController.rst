.. include:: ../../Includes.txt

==========================================================
Feature: #84983 - BE ViewHelper for EditDocumentController
==========================================================

See :issue:`84983`

Description
===========

Linking to FormEngine / EditDocumentController in fluid templates of backend modules
to edit database records and to create new records, has been simplified by introducing
a series of new view helpers.

Usage:
======

New record URI and Link view helper
-----------------------------------

Outputs the uri / the link to bring up FormEngine with the create new record form.

Available view helper arguments:

table
    Mandatory. The database table the record belongs to.

uid
    Optional. Negative value of the uid of the record the new record should be placed after. Must be negative!

pid
    Optional. The pid of the page the record should be placed to. Must be zero or positive. If not given, defaults to zero (root page).

returnUrl
    Optional. If given, the form redirects to that URL after save / close.

.. note::

    The ViewHelper accepts either pid or uid to be set, not both. If none is set, the record will be put to pid 0,
    if the TCA configuration of the table allows this.


.. code-block:: html

    <!-- URI to add a new news record on page 11 -->
    <be:uri.newRecord pid="11" table="tx_news_domain_model_news" />

    <!-- URI to add a new news record on root page -->
    <be:uri.newRecord table="tx_news_domain_model_news" />

    <!-- URI to add a new news record sorted after news record 17 and on the same pid as record 17 -->
    <be:uri.newRecord uid=-17 table="tx_news_domain_model_news" />

    <!-- Full link to add a new news record on page 25 -->
    <be:link.newRecord pid="25" table="tx_news_domain_model_news">create news article</be:link.newRecord>


Edit record URI and Link view helper
------------------------------------

Outputs the uri / the link to bring up FormEngine with the records edit form.

Available view helper arguments:

table
    Mandatory. The database table the record belongs to.

uid
    Mandatory. The uid of the record to be edited.

returnUrl
    Optional. If given, the form redirects to that URL after save / close.


.. code-block:: html

    <!-- URI to edit the news record with uid 43 -->
    <be:uri.editRecord uid="43" table="tx_news_domain_model_news" />

    <!-- Link to edit the news record with uid 43 -->
    <be:link.editRecord uid="43" table="tx_news_domain_model_news" />


Impact
======

Extensions must no longer provide their own ViewHelpers for editing and creating records. The ones provided from ext:backend are public API.

.. index:: Backend, Fluid, ext:backend
