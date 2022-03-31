.. include:: /Includes.rst.txt

========================================================
Important: #92202 - Remove exclude from important fields
========================================================

See :issue:`92202`

Description
===========

To simplify the setup of permissions, the following fields are now shown always to every editor:

* Field "colPos" from table "tt_content"
* Field "slug" from table "pages"

If the fields should be hidden, either the setting :php:`'exclude' => true` can be set in your
site package extension or the following TsConfig can be used:

.. code-block:: typoscript

    TCEFORM.pages.slug.disabled = 1
    TCEFORM.tt_content.colPos.disabled = 1

.. index:: Backend, ext:core, TCA
