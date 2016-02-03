====================================================
Feature: #38942 - Content- and Page-info ViewHelpers
====================================================

Description
===========

ViewHelpers to return or assign as template variable the information arrays from page and content respectively.

``f:info.content`` and ``f:info.page`` have been added, both of which support the ``as`` argument as known from other helpers.

.. code-block:: xml

	<f:info.content as="contentInfo">
	    The content element header is {contentInfo.header}.
	</f:info.content>

	<f:info.page as="pageInfo">
	    The page title is {pageInfo.title}
	</f:info.page>

	<f:alias map="{contentInfo: '{f:info.content()}', pageInfo: '{f:info.page()}'}">
	    The content element header is {contentInfo.header} and the page title is {pageInfo.title}
	</f:alias>

Impact
======

New ViewHelpers ``f:info.content`` and ``f:info.page`` become available.
