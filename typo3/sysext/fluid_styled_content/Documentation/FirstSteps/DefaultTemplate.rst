.. include:: /Includes.rst.txt

.. _include-default-typoscript:
.. _using-the-rendering-definitions:

==================================
Include the default TypoScript set
==================================

To use the default rendering definitions provided by *fluid_styled_content*, you
have to add the extension's TypoScript set to your root TypoScript record.

When you are using a site package you can add the following lines to your site
packages constants.typoscript and setup.typoscript:

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

Alternative: Include the TypoScript set in the root TypoScript record
=====================================================================

It is also still possible to include the TypoScript set directly into
a TypoScript record. However there are draw backs:
The import is then stored in the database and not the file system and cannot be
kept under version control.

.. include:: /Images/AutomaticScreenshots/TypoScript/EditTemplateRecord.rst.txt

.. rst-class:: bignums-xxl

1.  Go to the module :guilabel:`Web > TypoScript`.

2.  In the page tree, select the page which contains the root TypoScript
    record of your website.

3.  Select :guilabel:`Edit TypoScript Record` in the dropdown at the top of the
    :guilabel:`Web > TypoScript` module.

4.  Click the :guilabel:`Edit the whole TypoScript record`. This will
    open all the settings of the root TypoScript record:

.. include:: /Images/AutomaticScreenshots/TypoScript/IncludeTypoScriptTemplate.rst.txt

Go to the tab :guilabel:`Includes` and select
:guilabel:`Fluid Content Elements` in the
:guilabel:`Available items` under :guilabel:`Include TypoScript sets`.
The selection will move to the :guilabel:`Selected items`.

TYPO3 is now using the rendering definitions of *fluid_styled_content* for
the default set of content elements. This is essentially unstyled HTML5 markup.

You can additionally select :guilabel:`Fluid Content Elements CSS (optional)`.
This TypoScript set adds some CSS styling to make sure all
the parts of a content elements have some styling, this will include alignment and positioning.
This set of styles will not add any colors, make any changes to typography or anything else related to
your website's visual style. This TypoScript set is optional, as it is common for integrators to
override the basic styling.

Save the TypoScript record by using the save button at the top of the module.

Next step
=========

:ref:`Display the content elements <inserting-content-page-template>` in your
site package template.
