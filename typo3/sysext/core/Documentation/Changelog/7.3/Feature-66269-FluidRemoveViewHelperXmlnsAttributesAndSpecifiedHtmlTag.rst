
.. include:: /Includes.rst.txt

==================================================================================
Feature: #66269 - Fluid: Remove ViewHelper xmlns-attributes and specified html tag
==================================================================================

See :issue:`66269`

Description
===========

With the introduction of using xmlns:* attributes to include ViewHelpers, it is possible to have IDE support for Fluid
templates.
However, the problem is that the xmlns:* attributes and the corresponding tag will also be rendered, which is not
desired most of the time. A workaround to avoid this is to use sections.
However, this solution is counter-intuitive, is not available in layouts and causes extra processing overhead.

.. code-block:: html

	<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
			xmlns:n="http://typo3.org/ns/GeorgRinger/News/ViewHelpers">

	<f:section name="content">
	</f:section>


Impact
======

The xmlns:* attributes for valid ViewHelper namespaces will now be removed before rendering.
Such ViewHelper namespaces follow this URI pattern:

.. code-block:: html

	http://typo3.org/ns/<phpNamespace>


Examples:

.. code-block:: html

	http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers
	http://typo3.org/ns/GeorgRinger/News/ViewHelpers


xmlns attributes for non-ViewHelper namespaces will be preserved.

Furthermore an additional data-attribute to HTML-Tags is introduced.

.. code-block:: html

	data-namespace-typo3-fluid="true"

If this attribute is specified on the HTML-Tag, the HTML-tag itself won't be rendered as well.
(Also a corresponding closing tag will not be rendered for that template.)
This is useful for various IDEs and HTML auto-completion.


Examples
========

Include ViewHelper namespaces on an existing tag (e.g. root xml tag) via xmlns attributes for Fluid and News extension.

.. code-block:: xml

	<?xml version="1.0" encoding="utf-8"?>
	<root xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
			xmlns:n="http://typo3.org/ns/GeorgRinger/News/ViewHelpers"
			xmlns:foo="http://typo3.org/foo">

		<f:if condition="{newsItem.title}">
			<f:then>
				<n:titleTag>{newsItem.title}</n:titleTag>
			</f:then>
			<f:else>
				<n:titleTag>News-Detail</n:titleTag>
			</f:else>
		</f:if>
	</root>

Output is then

.. code-block:: xml

	<root xmlns:foo="http://typo3.org/foo" >
		...
	</root>


Include ViewHelper namespaces with HTML-tag and a data-namespace-typo3-fluid="true" attribute via xmlns attributes for
Fluid and News extension.

.. code-block:: html

	<html data-namespace-typo3-fluid="true"
			xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
			xmlns:n="http://typo3.org/ns/GeorgRinger/News/ViewHelpers">

		<f:if condition="{newsItem.title}">
			<f:then>
				<n:titleTag>{newsItem.title}</n:titleTag>
			</f:then>
			<f:else>
				<n:titleTag>News-Detail</n:titleTag>
			</f:else>
		</f:if>
	</html>

The output contains everything excluding the HTML-tag.


.. index:: Fluid
