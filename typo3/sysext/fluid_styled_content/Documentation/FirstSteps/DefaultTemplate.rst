.. include:: /Includes.rst.txt

.. _include-default-typoscript:
.. _using-the-rendering-definitions:

==============================
Include the default TypoScript
==============================

To use the default rendering definitions provided by *fluid_styled_content*, you
have to add the extension's static TypoScript template to your root template.

When you are using a site package you can add the following lines to your site
packages setup.typoscript and constants.typoscript:

.. code-block:: typoscript
    :caption: my_sitepackage/Configuration/TypoScript/constants.typoscript

    # Import the default constants of EXT:fluid_styled_content
    @import 'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript'

.. code-block:: typoscript
    :caption: my_sitepackage/Configuration/TypoScript/setup.typoscript

    # Import the default setup of EXT:fluid_styled_content
    @import 'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript'

    # Import the default CSS of EXT:fluid_styled_content
    @import 'EXT:fluid_styled_content/Configuration/TypoScript/Styling/setup.typoscript'

This is the recommended way as the import of TypoScript can be kept under
version control this way.

Alternative: Include the template in the root TypoScript record
===============================================================

It is also still possible to include the TypoScript templates directly into
the TypoScript template database record. However there are draw backs:
The import is then stored in the database and not the file system and cannot be
kept under version control.

.. include:: /Images/AutomaticScreenshots/TypoScript/EditTemplateRecord.rst.txt

.. rst-class:: bignums-xxl

1.  Go to the module :guilabel:`Web > Template`.

2.  In the page tree, select the page which contains the root template
    of your website.

3.  Select :guilabel:`Info/Modify` in the dropdown at the top of the
    :guilabel:`Web > Template` module.

4.  Click the :guilabel:`Edit the whole template record`. This will open all
    the settings of the root template:

.. include:: /Images/AutomaticScreenshots/TypoScript/IncludeTypoScriptTemplate.rst.txt

Go to the tab :guilabel:`Includes` and select
:guilabel:`Fluid Content Elements` in the
:guilabel:`Available items` under :guilabel:`Include static (from extensions)`.
The selection will move to the :guilabel:`Selected items`.

TYPO3 is now using the rendering definitions of *fluid_styled_content* for
the default set of content elements. This is essentially unstyled HTML5 markup.

You can additionally select :guilabel:`Fluid Content Elements CSS (optional)`.
This template adds some CSS styling to make sure all
the parts of a content elements have some styling, this will include alignment and positioning.
This set of styles will not add any colors, make any changes to typography or anything else related to
your website's visual style. This static include is optional, as it is common for integrators to
override the basic styling.

Save the template by using the save button at the top of the module.

Next step
=========

:ref:`Display the content elements <inserting-content-page-template>` in your
site package template.
