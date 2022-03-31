
.. include:: /Includes.rst.txt

==================================================
Feature: #63729 - API for Twitter Bootstrap modals
==================================================

See :issue:`63729`

Description
===========

Actions that require a users' attention must be visualized by modal windows. TWBS provides those,
but extension authors or core developers must take care of its creation and handling themselves.

This API provides a basis to create modal windows with severity representation. For a better UX,
if actions (buttons) are attached to the modal, one button must be a positive action. This button
should get a btnClass and set as active.

Modals should be used rarely and only for confirmations. For information the TYPO3.Flashmessage API should be used.
For complex content, like forms or a lot of information, please use normal pages.


Impact
======

API
---

The API provides only two public methods:

#. :code:`TYPO3.Modal.confirm(title, content, severity, buttons)`
#. :code:`TYPO3.Modal.dismiss()`

Modal Settings
~~~~~~~~~~~~~~

========= =============== ============ ======================================================================================================
Name      DataType        Mandatory    Description
========= =============== ============ ======================================================================================================
title     string          Yes          The title displayed in the modal
content   string|jQuery   Yes          The content displayed in the modal
severity  int                          Represents the severity of a modal. Please see TYPO3.Severity. Default is :code:`TYPO3.Severity.info`.
buttons   object[]                     Actions rendered into the modal footer. If empty, the footer is not rendered. See table below.
========= =============== ============ ======================================================================================================

Button Settings
~~~~~~~~~~~~~~~

========= =============== ============ ===============================================================
Name      DataType        Mandatory    Description
========= =============== ============ ===============================================================
text      string          Yes          The text rendered into the button.
trigger   function        Yes          Callback that's triggered on button click.
active    bool                         Marks the button as active. If true, the button gets the focus.
btnClass  string                       The css class for the button
========= =============== ============ ===============================================================

Data-Attributes
~~~~~~~~~~~~~~~

It is also possible to use data-attributes to trigger a modal.
e.g. on an anchor element, which prevents the default behavior.

========================= ==================================================================
Name                      Description
========================= ==================================================================
data-title                the title text for the modal
data-content              the content text for the modal
data-severity             the severity for the modal, default is info (see TYPO3.Severity.*)
data-href                 the target URL, default is the href attribute of the element
data-button-close-text    button text for the close/cancel button
data-button-ok-text       button text for the ok button
========================= ==================================================================

:code:`class="t3js-modal-trigger"` marks the element as modal trigger

Examples
--------

A basic modal without any specials can be created this way:

.. code-block:: javascript

	TYPO3.Modal.confirm('The title of the modal', 'This the the body of the modal');

A modal as warning with button:

.. code-block:: javascript

	TYPO3.Modal.confirm('Warning', 'You may break the internet!', TYPO3.Severity.warning, [
		{
			text: 'Break it',
			active: true,
			trigger: function() {
				// break the net
			}
		}, {
			text: 'Abort!',
			trigger: function() {
				TYPO3.Modal.dismiss();
			}
		}
	]);

A modal as warning:

.. code-block:: javascript

	TYPO3.Modal.confirm('Warning', 'You may break the internet!', TYPO3.Severity.warning);

A modal triggered on an anchor element:

.. code-block:: html

	<a href="delete.php" class="t3js-modal-trigger" data-title="Delete" data-content="Really delete?">delete</a>


.. index:: PHP-API, JavaScript, Backend
