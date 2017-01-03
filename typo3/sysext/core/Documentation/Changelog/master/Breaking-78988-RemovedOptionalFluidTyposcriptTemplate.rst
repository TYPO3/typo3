.. include:: ../../Includes.txt

=============================================================
Breaking: #78988 - Removed optional fluid typoscript template
=============================================================

See :issue:`78988`

Description
===========

Static include file "Fluid: (Optional) default ajax configuration (fluid)" was meant as an
example/showcase on how to use Fluid Widgets in FE. But the current used includes are outdated or
broken. Furthermore the way of including files with `page.headerData` instead of
`page.inlcudeJSLibs` or `page.includeCSSLibs` is not the prefered way anymore. Also in many
situations this way of including JavaScript and CSS conflicts with other included JavaScript libs
and CSS files.

Including the files manually has many benefits:

- more control of what versions of the javascript libs are included
- no double jquery.js includes
- more control of adjusting styling without resetting/overriding styles deliverd by jquery-ui-theme.css


Impact
======

The jQuery JavaScript and CSS files are not included anymore so the AJAX handling in the front-end
will not work anymore when the site relies on these files.


Affected Installations
======================

All installations that depend on the jQuery includes added by the static typoscript template
"Fluid: (Optional) default ajax configuration (fluid)".


Migration
=========

Include the needed file manually in your typoscript template.

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