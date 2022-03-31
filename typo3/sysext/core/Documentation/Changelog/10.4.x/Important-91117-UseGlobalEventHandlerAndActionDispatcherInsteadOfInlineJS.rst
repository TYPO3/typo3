.. include:: /Includes.rst.txt

====================================================================================
Important: #91117 - Use GlobalEventHandler and ActionDispatcher instead of inline JS
====================================================================================

See :issue:`91117`

Description
===========

In order to reduce the amount of inline JavaScript (with the goal to pave the
way towards stronger Content-Security-Policy assignments) lots of inline JavaScript
code parts have been substituted by a declarative syntax - basically using HTML
:html:`data-*` attributes.

The following list collects an overview of common JavaScript snippets and their
corresponding substitute using modules :js:`TYPO3/CMS/Backend/GlobalEventHandler`
and :js:`TYPO3/CMS/Backend/ActionDispatcher`.


`TYPO3/CMS/Backend/GlobalEventHandler`
--------------------------------------

.. code-block:: html

   <select onchange="window.location.href=this.options[this.selectedIndex].value;">'
   <!-- ... changed to ... -->
   <select data-global-event="change" data-action-navigate="$value">'

Navigates to URL once selected drop-down was changed
(`$value` refers to selected value)


.. code-block:: html

   <select value="0" name="depth"
      onchange="window.location.href='https://example.org/__VAL__'.replace(/__VAL__/, this.options[this.selectedIndex].value);">
   <!-- ... changed to ... -->
   <select value="0" name="depth" data-global-event="change"
      data-action-navigate="$data=~s/$value/" data-navigate-value="https://example.org/${value}">

Navigates to URL once selected drop-down was changed, including selected value
(`$data` refers to value of :html:`data-navigate-value`, `$value` to selected value,
`$data=~s/$value/` replaces literal `${value}` with selected value in `:html:`data-navigate-value`)


.. code-block:: html

   <input type="checkbox" name="setting" onclick="window.location.href='/?setting='+(this.checked ? 1 : 0)">
   <!-- ... changed to ... -->
   <input type="checkbox" name="setting" value="1" data-empty-value="0"
      data-global-event="change" data-action-navigate="$data=~s/$value/">

Checkboxes used to send a particular value when being unchecked can be achieved by using
:html:`data-empty-value="0"` - in case this attribute is omitted, an empty string `''` is sent.


.. code-block:: html

   <input type="checkbox" onclick="document.getElementById('formIdentifier').submit();">
   <!-- ... changed to ... -->
   <input type="checkbox" data-global-event="change" data-action-submit="$form">
   <!-- ... or (using CSS selector) ... -->
   <input type="checkbox" data-global-event="change" data-action-submit="#formIdentifier">

Submits a form once a value has been changed
(`$form` refers to paren form element, using CSS selectors like `#formIdentifier`
is possible as well)


`TYPO3/CMS/Backend/ActionDispatcher`
------------------------------------

.. code-block:: html

   <a href="#" onclick="top.TYPO3.InfoWindow.showItem('tt_content', 123); return false;">
   <!-- ... changed to ... -->
   data-dispatch-action="TYPO3.InfoWindow.showItem" data-dispatch-args-list="be_users,123">
   <!-- ... or (using JSON arguments) ... -->
   data-dispatch-action="TYPO3.InfoWindow.showItem" data-dispatch-args="[&quot;tt_content&quot;,123]">

Invokes :js:`TYPO3.InfoWindow.showItem` module function to display details for a given
record (of database table `tt_content`, having `uid=123` in the example above)


.. index:: Backend, JavaScript, ext:backend
