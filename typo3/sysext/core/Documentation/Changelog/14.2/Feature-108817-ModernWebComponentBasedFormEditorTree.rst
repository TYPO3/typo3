..  include:: /Includes.rst.txt

..  _feature-108817-1739494800:

=================================================================
Feature: #108817 - Introduce web component-based form editor tree
=================================================================

See :issue:`108817`

Description
===========

The Form Editor tree component has been completely modernized. It has been  migrated from
a legacy jQuery-based implementation to a modern web component architecture
using Lit and the TYPO3 backend tree infrastructure.

Enhanced user experience
------------------------

The new tree component provides a significantly improved user experience with
modern interaction patterns and visual feedback:

**Intuitive drag and drop**
    Form elements can now be reorganized using a smooth drag-and-drop interface
    with intelligent validation rules. The tree automatically prevents invalid
    operations, such as dragging the root form element, moving pages outside
    their designated level, or dropping elements into non-composite types.

**Smart element organization**
    Only composite elements like grid containers and fieldsets can receive
    child elements, while simple form fields remain non-droppable. Pages must
    always stay at the top level, ensuring proper form structure. The tree
    automatically distinguishes between reordering siblings and changing parent
    elements, providing precise control over form organization.

**Visual feedback**
    Clear visual indicators show valid drop zones during drag operations.
    Selected elements are highlighted with proper styling. The tree provides
    immediate feedback for all interactions, making form building more
    intuitive.

**Persistent navigation**
    The tree remembers expanded and collapsed states. After drag
    and drop operations, the tree maintains your current view and selection,
    preventing disorientating resets. Navigation feels natural and responsive.

**Integrated search**
    A built-in search toolbar allows quick filtering of form elements by name.
    The search works client-side for instant results, making it easy to locate
    specific elements in complex forms.

**Collapse-all functionality**
    The toolbar includes a convenient button to collapse all expanded nodes at
    once, helping to get a quick overview of your form structure or reset the
    view to a clean state.

Technical implementation
------------------------

The new implementation leverages the proven TYPO3 backend tree infrastructure.

Impact
======

Form editors will immediately notice the improved responsiveness and modern
feel of the tree component. Drag-and-drop operations are smoother and more
predictable. The search functionality makes working with large forms
significantly easier. The tree maintains its state during operations, reducing
friction and improving workflow efficiency.

The new web component-based architecture ensures better maintainability and
extensibility for future enhancements. The component integrates seamlessly with
the existing Form Editor without requiring changes to form definitions or
configuration.

..  index:: Backend, JavaScript, ext:form
