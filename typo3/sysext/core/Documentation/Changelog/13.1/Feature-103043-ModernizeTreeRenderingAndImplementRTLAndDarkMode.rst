.. include:: /Includes.rst.txt

.. _feature-103043-1707113495:

===========================================================================
Feature: #103043 - Modernize tree rendering and implement RTL and dark mode
===========================================================================

See :issue:`103043`

Description
===========

The Tree feature in TYPO3 stands as one of its most iconic and widely used
components, offering a visual representation of site structures to editors
globally. Serving various purposes, such as file management, category/record
selection, navigation, and more, the Tree has been a cornerstone for content
handling.

Originally introduced in Version 8 with a performance-oriented approach, the
SVG tree, powered by d3js, has faithfully served the community for the past
seven years. While it excelled in providing a fast and efficient experience,
it had its share of challenges, particularly due to its reliance on SVG and
d3js.

Challenges with the SVG tree:

- Limited functionality due to SVG constraints
- Maintenance complexities with code-built SVG
- Accessibility challenges
- Difficulty in extension and innovation
- Lack of native drag and drop
- Complexity hindering understanding for many

Recognizing these challenges, we embarked on a journey to reimagine the Tree
component, paving the way for a more adaptable and user-friendly experience.

Introducing the Modern Reactive Tree:

The new Tree, built on contemporary web standards, bids farewell to the
SVG tree's limitations. Embracing native drag and drop APIs, standard HTML
markup, and CSS for styling, the Modern Reactive Tree promises improved
maintainability and accessibility.

Key enhancements:

- Unified experience: All features are now consolidated into the base tree,
  ensuring a seamless and consistent user experience. This encompasses data
  loading and processing, selection, keyboard navigation, drag and drop, and
  basic node editing.

- User preferences: The tree now dynamically adjusts to user preferences,
  supporting both light/dark mode and left-to-right (LTR) or right-to-left
  (RTL) writing modes.

- Reactive rendering: Adopting a modern reactive rendering approach, the tree
  and its nodes now autonomously redraw themselves based on property changes,
  ensuring a smoother and more responsive interface.

- Native drag and drop: Leveraging native drag and drop functionality opens
  up avenues for future enhancements, such as dragging content directly onto
  a page or seamlessly moving elements between browser windows.

- Improved API endpoints: All endpoints delivering data for the tree now adhere
  to a defined API definition, enhancing consistency and compatibility with
  existing integrations.

- Unified dragging tooltip handling: The dragging tooltip handling has been
  adjusted to a unified component that can be utilized across all components,
  ensuring synchronization across browser windows.

- Dynamic tree status storage: The Pagetree status is no longer stored in the
  database. Instead, it is now stored in the local storage of the user's
  browser. This change empowers the browser to control the tree status, making
  it more convenient for users to transition between multiple browsers or
  machines.

- Enhanced virtual scroll: The virtual scroll of the tree has been improved,
  ensuring that only nodes currently visible to the user are rendered to the
  DOM. Additionally, the focus on selected nodes is maintained even when
  scrolled out of view, providing a smoother and more user-friendly experience.

As we transition to this Modern Reactive Tree, we anticipate a renewed era of
flexibility, ease of use, and potential for exciting future features.


Impact
======

The TYPO3 CMS Tree modernization brings:

- Personalization:
  Adapts to user preferences for a tailored interface.

- Reactive design:
  Ensures smoother interactions.

- Efficient integration:
  Improved API endpoints for seamless data exchange.

- Consistency across devices:
  Unified dragging and dynamic storage for a consistent experience.

- Enhanced performance:
  Optimal rendering during navigation.

These changes collectively enhance usability, adaptability, and performance,
elevating the TYPO3 CMS Tree experience.

.. index:: Backend, ext:backend
