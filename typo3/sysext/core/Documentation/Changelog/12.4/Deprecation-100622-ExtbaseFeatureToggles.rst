.. include:: /Includes.rst.txt

.. _deprecation-100622-1681664078:

==============================================
Deprecation: #100622 - Extbase feature toggles
==============================================

See :issue:`100622`

Description
===========

Extbase has an own system for feature toggles next to the Core feature
toggle API. It has always been marked as internal, but is used for
a couple of toggles within the Extbase framework.

All toggles and the internal PHP API have been marked as deprecated
in TYPO3 v12 and should be avoided.


Impact
======

The PHP API for Extbase toggles has always been marked as internal. It will
be removed with TYPO3 v13.

The single toggles can still be used in TYPO3 v12, but their triggered
functionality will be removed with TYPO3 v13, if set to `1`.


Affected installations
======================

Extensions should not rely on
:php:`\TYPO3\CMS\Extbase\ConfigurationConfigurationManagerInterface->isFeatureEnabled()`.
The method is marked as internal and should never have been used by extensions. The
extension scanner still finds usages of this method in extensions as weak match.

All feature toggles have been marked as deprecated. Setting one of them to `1` in
TypoScript will trigger a deprecation level log message, they will stop working with
TYPO3 v13.


Migration
=========

Extbase has three feature toggles in TYPO3 v12. All of them will be removed
with TYPO3 v13. Instances with extensions setting those to `1` in TypoScript
may need adaptions. Instances setting the toggles to `0` can simply remove them
from TypoScript.

skipDefaultArguments = 1
------------------------

This is an ancient toggle that was used before routing has been added with
TYPO3 v9. It allowed to skip the `controller` and `action` argument in frontend
plugin links, when linking to the default Extbase controller / action combination.
This toggle has been documented as being broken in combination with
:ref:`Extbase plugin enhancer <t3coreapi:routing-extbase-plugin-enhancer>` already.
Consuming instances should switch to proper routing configuration instead.

ignoreAllEnableFieldsInBe = 1
-----------------------------

This is another ancient toggle that triggers
:php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->setIgnoreEnableFields(true)`
for Extbase repositories when used in backend scope. It allows ignoring default :php:`TCA`
flags like suppressing of deleted records in queries.

Extbase-based backend modules that rely on this toggle being set to `1` can easily
migrate this: When the repository in question is only used in backend context, the
code below should trigger the same behavior. Note as with other query settings,
this toggle needs to be used with care, otherwise backend users may see records
they are not supposed to see.

..  code-block::php

    /**
     * Overwrite createQuery to not respect enable fields.
     */
    public function createQuery(): QueryInterface
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        return $query;
    }

When the repository is used in both backend and frontend context, the code
should be refactored a bit towards a public method that can be set by the
Extbase backend controller only.

enableNamespacedArgumentsForBackend = 1
---------------------------------------

This toggle has been introduced in TYPO3 v12. See :ref:`feature-97096`
for more details. Extbase backend modules should no longer expect the
namespace to be set. It may be necessary to adapt some Ajax calls and
request-related argument checks in custom modules.


.. index:: PHP-API, TypoScript, PartiallyScanned, ext:extbase
