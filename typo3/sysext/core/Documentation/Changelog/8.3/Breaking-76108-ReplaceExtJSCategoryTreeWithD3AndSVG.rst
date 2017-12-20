
.. include:: ../../Includes.txt

==============================================================
Breaking: #76108 - Replace ExtJS category tree with D3 and SVG
==============================================================

See :issue:`76108`

Description
===========

Backend ExtJS category tree has been replaced with one based on D3.js and SVG.
The js file `typo3/sysext/backend/Resources/Public/JavaScript/tree.js` has been removed.

The expanded/collapsed state will not be saved to the backend user settings any more.
It was not used in the core, as all category trees have setting 'expandAll' set to true.
It also polluted backend user settings with tons of data without giving much usability gain.

Impact
======

Any JS code referencing ExtJS component :javascript:`TYPO3.Components.Tree` or its sub-components
(like :javascript:`TYPO3.Components.Tree.StandardTree`) will no longer work.


Affected Installations
======================

All installations having extensions which modify the :javascript:`TYPO3.Components.Tree`
(implemented in `typo3/sysext/backend/Resources/Public/JavaScript/tree.js`) component,
or rely on the file being present.


Migration
=========

Migration of the JS code to the new `SvgTree` component is recommended.

.. index:: JavaScript, Backend
