.. include:: /Includes.rst.txt

============================================================
Breaking: #78988 - Remove optional Fluid TypoScript template
============================================================

See :issue:`78988`

Description
===========

The static include file "Fluid: (Optional) default ajax configuration (fluid)" was meant as an
example/showcase on how to use Fluid Widgets in FE. But the currently used includes are outdated or
broken. Furthermore the way of including files with :typoscript:`page.headerData` instead of
:typoscript:`page.includeJSLibs` or :typoscript:`page.includeCSSLibs` is not the preferred way anymore. Also in many
situations this way of including JavaScript and CSS conflicts with other included JavaScript libs
and CSS files.

Including the files manually has many benefits:

- more control of what versions of the JavaScript libs are included
- prevent multiple jquery.js includes
- more control of adjusting styling without resetting/overriding styles delivered by jquery-ui-theme.css


Impact
======

The jQuery JavaScript and CSS files are not included anymore so the AJAX handling in the frontend
will not work anymore when the site relies on these files.


Affected Installations
======================

All installations that depend on the jQuery includes added by the static TypoScript template
"Fluid: (Optional) default ajax configuration (fluid)".


Migration
=========

Include the needed file manually in your TypoScript template.

.. code-block:: typoscript

    page.includeJSLibs {
        jquery = https://code.jquery.com/jquery-3.1.1.slim.min.js
        jquery.external = 1
        jquery.integrity = sha256-/SIrNqv8h6QGKDuNoLGA4iret+kyesCkHGzVUUV0shc=
        jqueryUi = https://code.jquery.com/ui/1.12.1/jquery-ui.min.js
        jqueryUi.external = 1
        jqueryUi.integrity = sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=
    }

    page.includeCSSLibs {
        jqueryUI = https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css
        jqueryUi.external = 1
    }

.. index:: Fluid, Frontend, JavaScript
