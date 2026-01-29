..  include:: /Includes.rst.txt

..  _feature-108817-1739494800:

=================================================================
Feature: #108817 - Introduce web component-based form editor tree
=================================================================

See :issue:`108817`

Description
===========

The Form Editor's tree component has been completely modernized, migrating from
the legacy jQuery-based implementation to a modern Web Components architecture
using Lit and the TYPO3 backend tree infrastructure.

Enhanced User Experience
-------------------------

The new tree component provides a significantly improved user experience with
modern interaction patterns and visual feedback:

**Intuitive Drag & Drop**
    Form elements can now be reorganized using a smooth drag and drop interface
    with intelligent validation rules. The tree automatically prevents invalid
    operations, such as dragging the root form element, moving pages outside
    their designated level, or dropping elements into non-composite types.

**Smart Element Organization**
    Only composite elements like Grid Containers and Fieldsets can receive
    child elements, while simple form fields remain non-droppable. Pages must
    always stay at the top level, ensuring proper form structure. The tree
    automatically distinguishes between reordering siblings and changing parent
    elements, providing precise control over form organization.

**Visual Feedback**
    Clear visual indicators show valid drop zones during drag operations.
    Selected elements are highlighted with proper styling. The tree provides
    immediate feedback for all interactions, making form building more
    intuitive.

**Persistent Navigation**
    The tree automatically remembers expanded and collapsed states. After drag
    and drop operations, the tree maintains your current view and selection,
    preventing disorienting resets. Navigation feels natural and responsive.

**Integrated Search**
    A built-in search toolbar allows quick filtering of form elements by name.
    The search works client-side for instant results, making it easy to locate
    specific elements in complex forms.

**Collapse All Functionality**
    The toolbar includes a convenient button to collapse all expanded nodes at
    once, helping to get a quick overview of your form structure or reset the
    view to a clean state.

Technical Implementation
------------------------

The new implementation leverages the proven TYPO3 backend tree infrastructure.

Impact
======

Form editors will immediately notice the improved responsiveness and modern
feel of the tree component. Drag and drop operations are smoother and more
predictable. The search functionality makes working with large forms
significantly easier. The tree maintains its state during operations, reducing
friction and improving workflow efficiency.

The new Web Component-based architecture ensures better maintainability and
extensibility for future enhancements. The component integrates seamlessly with
the existing Form Editor without requiring changes to form definitions or
configurations.

..  index:: Backend, JavaScript, ext:form
