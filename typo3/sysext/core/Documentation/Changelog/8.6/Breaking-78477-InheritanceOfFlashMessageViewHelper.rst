.. include:: /Includes.rst.txt

=====================================================================================
Breaking: #78477 - FlashMessagesViewHelper no longer inherits from TagBasedViewHelper
=====================================================================================

See :issue:`78477`

Description
===========

The :php:`FlashMessagesViewHelper` has been refactored and no longer inherits from the :php:`TagBasedViewHelper`.


Impact
======

The :php:`FlashMessagesViewHelper` outputs default context specific markup. Adding own classes or tag attributes is no longer possible.


Affected Installations
======================

All installations using the :php:`FlashMessagesViewHelper` with tag specific attributes.


Migration
=========

Remove the tag specific attributes and style the default output. If you need custom output use the possibility to render FlashMessages yourself, for example:

.. code-block:: html

	<f:flashMessages as="flashMessages">
	    <dl class="messages">
	        <f:for each="{flashMessages}" as="flashMessage">
	           <dt>CODE!! {flashMessage.code}</dt>
	           <dd>MESSAGE:: {flashMessage.message}</dd>
	        </f:for>
	    </dl>
	</f:flashMessages>

.. index:: Backend, Fluid, Frontend
