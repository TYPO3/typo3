.. include:: /Includes.rst.txt

.. _important-99490-1673358047:

======================================================================================
Important: #99490 - Provide tag to add JavaScript Modules to importmap in backend form
======================================================================================

See :issue:`99490`

Description
===========

The JavaScript module import map is static and only generated and
loaded in the first request to a document. All possible future
modules requested in later Ajax calls need to be registered already
in the first initial request.

We are adding a new tag `backend.form` that is used to identify
JavaScript modules that can be used within the backend forms. This
will ensure that the import maps are available for these modules
even if the element is not displayed directly.

A typical use case for this is an `InlineRelationRecord` where the
CKEditor is not part of the main record but needs to be loaded for
the child record.

Example Configuration/JavaScriptModules.php
-------------------------------------------

.. code-block:: php

    <?php

    return [
        'dependencies' => [
            'backend',
        ],
        'tags' => [
            'backend.form',
        ],
        'imports' => [
            '@typo3/rte-ckeditor/'
                => 'EXT:rte_ckeditor/Resources/Public/JavaScript/',
            '@typo3/ckeditor5-bundle.js'
                => 'EXT:rte_ckeditor/Resources/Public/Contrib/ckeditor5-bundle.js',
        ],
    ];


.. index:: Backend, FlexForm, JavaScript, ext:backend
