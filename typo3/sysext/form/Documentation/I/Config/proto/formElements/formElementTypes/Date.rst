.. include:: ../../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.date:

============
[Date]
============

The form framework contains a form element called 'Date' which is technically an HTML5 'date' form element.

The ``DateRange`` validator is the server side validation equivalent to the client side validation through the ``min``
and ``max`` HTML attribute and should always be used in combination. If the ``DateRange`` validator is added to the
form element within the form editor, the ``min`` and ``max`` HTML attributes are added automatically.

Browsers which do not support the HTML5 date element gracefully degrade to a text input. The HTML5 date element always
normalizes the value to the format Y-m-d (RFC 3339 'full-date'). With a text input, by default the browser has no
recognition of which format the date should be in. A workaroung could be to put a pattern attribute on the date input.
Even though the date input does not use it, the text input fallback will.

By default, the HTML attribute ``pattern="([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"`` is rendered on the
date form element. Note that this basic regular expression does not support leap years and does not check for the
correct number of days in a month. But as a start, this should be sufficient. The same pattern is used by the form
editor to validate the properties ``defaultValue`` and the ``DateRange`` valiator options ``minimum`` and ``maximum``.

Read more: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date#Handling_browser_support


.. _typo3.cms.form.prototypes.<prototypeidentifier>.formelementsdefinition.date-properties:

Properties
==========

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.implementationclassname:
.. include:: Date/implementationClassName.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.properties.containerclassattribute:
.. include:: Date/properties/containerClassAttribute.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.properties.displayFormat:
.. include:: Date/properties/displayFormat.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.properties.elementclassattribute:
.. include:: Date/properties/elementClassAttribute.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.properties.elementerrorclassattribute:
.. include:: Date/properties/elementErrorClassAttribute.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.properties.fluidAdditionalAttributes.pattern:
.. include:: Date/properties/fluidAdditionalAttributes/pattern.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor:
.. include:: Date/formEditor.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.editors.100:
.. include:: Date/formEditor/editors/100.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.editors.200:
.. include:: Date/formEditor/editors/200.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.editors.230:
.. include:: Date/formEditor/editors/230.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.editors.500:
.. include:: Date/formEditor/editors/500.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.editors.550:
.. include:: Date/formEditor/editors/550.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.editors.700:
.. include:: Date/formEditor/editors/700.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.editors.800:
.. include:: Date/formEditor/editors/800.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.editors.900:
.. include:: Date/formEditor/editors/900.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.editors.9999:
.. include:: Date/formEditor/editors/9999.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.predefineddefaults:
.. include:: Date/formEditor/predefinedDefaults.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.10:
.. include:: Date/formEditor/propertyCollections/validators/10.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.10.identifier:
.. include:: Date/formEditor/propertyCollections/validators/10/identifier.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.10.editors.100:
.. include:: Date/formEditor/propertyCollections/validators/10/editors/100.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.10.editors.200:
.. include:: Date/formEditor/propertyCollections/validators/10/editors/200.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.10.editors.250:
.. include:: Date/formEditor/propertyCollections/validators/10/editors/250.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.10.editors.300:
.. include:: Date/formEditor/propertyCollections/validators/10/editors/300.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.10.editors.9999:
.. include:: Date/formEditor/propertyCollections/validators/10/editors/9999.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.20:
.. include:: Date/formEditor/propertyCollections/validators/20.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.20.identifier:
.. include:: Date/formEditor/propertyCollections/validators/20/identifier.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.20.editors.100:
.. include:: Date/formEditor/propertyCollections/validators/20/editors/100.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.20.editors.200:
.. include:: Date/formEditor/propertyCollections/validators/20/editors/200.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.20.editors.9999:
.. include:: Date/formEditor/propertyCollections/validators/20/editors/9999.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.30:
.. include:: Date/formEditor/propertyCollections/validators/30.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.30.identifier:
.. include:: Date/formEditor/propertyCollections/validators/30/identifier.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.30.editors.100:
.. include:: Date/formEditor/propertyCollections/validators/30/editors/100.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.30.editors.200:
.. include:: Date/formEditor/propertyCollections/validators/30/editors/200.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.30.editors.300:
.. include:: Date/formEditor/propertyCollections/validators/30/editors/300.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.30.editors.400:
.. include:: Date/formEditor/propertyCollections/validators/30/editors/400.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.30.editors.9999:
.. include:: Date/formEditor/propertyCollections/validators/30/editors/9999.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.40:
.. include:: Date/formEditor/propertyCollections/validators/40.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.40.identifier:
.. include:: Date/formEditor/propertyCollections/validators/40/identifier.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.40.editors.100:
.. include:: Date/formEditor/propertyCollections/validators/40/editors/100.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.40.editors.200:
.. include:: Date/formEditor/propertyCollections/validators/40/editors/200.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.40.editors.9999:
.. include:: Date/formEditor/propertyCollections/validators/40/editors/9999.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.50:
.. include:: Date/formEditor/propertyCollections/validators/50.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.50.identifier:
.. include:: Date/formEditor/propertyCollections/validators/50/identifier.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.50.editors.100:
.. include:: Date/formEditor/propertyCollections/validators/50/editors/100.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.50.editors.200:
.. include:: Date/formEditor/propertyCollections/validators/50/editors/200.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.50.editors.9999:
.. include:: Date/formEditor/propertyCollections/validators/50/editors/9999.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.60:
.. include:: Date/formEditor/propertyCollections/validators/60.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.60.identifier:
.. include:: Date/formEditor/propertyCollections/validators/60/identifier.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.60.editors.100:
.. include:: Date/formEditor/propertyCollections/validators/60/editors/100.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.60.editors.200:
.. include:: Date/formEditor/propertyCollections/validators/60/editors/200.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.60.editors.9999:
.. include:: Date/formEditor/propertyCollections/validators/60/editors/9999.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.70:
.. include:: Date/formEditor/propertyCollections/validators/70.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.70.identifier:
.. include:: Date/formEditor/propertyCollections/validators/70/identifier.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.70.editors.100:
.. include:: Date/formEditor/propertyCollections/validators/70/editors/100.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.70.editors.200:
.. include:: Date/formEditor/propertyCollections/validators/70/editors/200.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.70.editors.300:
.. include:: Date/formEditor/propertyCollections/validators/70/editors/300.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.70.editors.400:
.. include:: Date/formEditor/propertyCollections/validators/70/editors/400.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.70.editors.9999:
.. include:: Date/formEditor/propertyCollections/validators/70/editors/9999.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.80:
.. include:: Date/formEditor/propertyCollections/validators/80.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.80.identifier:
.. include:: Date/formEditor/propertyCollections/validators/80/identifier.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.80.editors.100:
.. include:: Date/formEditor/propertyCollections/validators/80/editors/100.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.80.editors.200:
.. include:: Date/formEditor/propertyCollections/validators/80/editors/200.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.80.editors.300:
.. include:: Date/formEditor/propertyCollections/validators/80/editors/300.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.propertycollections.validators.80.editors.9999:
.. include:: Date/formEditor/propertyCollections/validators/80/editors/9999.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.label:
.. include:: Date/formEditor/label.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.group:
.. include:: Date/formEditor/group.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.groupsorting:
.. include:: Date/formEditor/groupSorting.rst

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.formelementsdefinition.date.formeditor.iconidentifier:
.. include:: Date/formEditor/iconIdentifier.rst
