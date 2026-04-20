..  include:: /Includes.rst.txt

..  _feature-108915-1742484720:

===========================================
Feature: #108915 - New page creation wizard
===========================================

See :issue:`108915`

Description
===========

The TYPO3 backend now features a new guided "Page Creation Wizard" designed to
streamline the page creation process. This interface replaces the traditional,
technically complex workflow with a modular and accessible step-by-step
process.

The primary goals of the wizard are to ensure data integrity by enforcing
mandatory fields during creation which improves accessibility, and provides a modern,
responsive user experience that does not require deep TYPO3-specific expertise.

Key features
------------

*   **Guided workflow:** A step-by-step process including positioning, type
    selection, data entry, and a final review before persistence.
*   **Data integrity:** Validation of required fields (for example, page title)
    occurs at each step to prevent broken or incomplete page records.
*   **Context-aware:** The wizard can be triggered from various entry points
    (for example, page tree, :guilabel:`Content > Records` module) and respects
    predefined parameters like position and page type.
*   **Modular and extensible:** Built using a generic architecture that allows
    integrators to add custom steps or extend existing configuration for
    specific page types.
*   **FormEngine integration:** Dynamic steps are rendered using FormEngine,
    ensuring that all TCA-based rules and field configurations are respected.
*   **Post-creation actions:** After successful creation, users can choose whether to
    jump to the :guilabel:`Content > Layout` module, create another page,
    or return to their previous task.

Impact
======

Editors benefit from a faster, less error-prone way to build page structures.
The intuitive interface significantly lowers the barrier to entry for new users
while maintaining the flexibility required by power users.

Developers and integrators can leverage the modular design to customize the
creation process for custom `doktype` values or even adapt the wizard concept
for other TYPO3 workflows in the future.

..  index:: Backend, ext:backend
