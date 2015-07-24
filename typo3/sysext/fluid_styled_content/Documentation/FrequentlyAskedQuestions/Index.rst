.. include:: ../Includes.txt

.. _frequently-asked-questions:

==========================
Frequently Asked Questions
==========================

- **Q**: I don't see all the backend fields I can use for the configuration of a content
  element.

  **A**: Compared to *css_styled_content*, there is a difference
  in the availability of fields, but also in the ordering and placement in tabs. We have
  removed some fields which are not suitable for an editor or should be handled by by CSS.

- **Q**: The "New Content Element" Wizards does not show the general content elements.

  **A**: The PageTsconfig of "fluid_styled_config" needs to be loaded on the page to make
  the content elements appear in the wizard. You can include it globally by checking
  the designated checkbox in the extension configuration. See :ref:`extension-manager` for
  more information.

  If you want to include it only in a specific page tree open the page properties of the
  topmost page in the tree and head to the "Resources" tab. Here you find the field
  **Include Page TSConfig (from extensions):** where you can add the prepared PageTSconfig
  "Fluid-based Content Elements (fluid_styled_content)"
