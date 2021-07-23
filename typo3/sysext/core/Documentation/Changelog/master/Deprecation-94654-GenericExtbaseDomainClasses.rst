.. include:: ../../Includes.txt

====================================================
Deprecation: #94654 - Generic extbase domain classes
====================================================

See :issue:`94654`

Description
===========

Most extbase "generic" domain model and repositories have been deprecated:
They are opinionated implementations and can't be "correct" since the
domains they are used in, are unique.

The following classes have been deprecated:

* :php:`TYPO3\CMS\Extbase\Domain\Model\BackendUser`
* :php:`TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup`
* :php:`TYPO3\CMS\Extbase\Domain\Model\FrontendUser`
* :php:`TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup`
* :php:`TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository`
* :php:`TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository`
* :php:`TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository`
* :php:`TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository`
* :php:`TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository`


Impact
======

Using or extending the above classes is deprecated since core v11.
They will be removed with core v12.


Affected Installations
======================

Various extbase based extensions may use or extend the classes. The
extension scanner will find usages with a strong match.


Migration
=========

The migration paths are usually straight forward.

Extensions that extend the repository classes should extend extbase
:php:`TYPO3\CMS\Extbase\Persistence\Repository` instead and maybe copy
body methods like :php:`initializeObject()` if given and not overridden
already.

Extensions that use the extbase repositories directly should copy the
class to their extension namespace and use the own ones instead.

Extensions that extend the model classes should extend
:php:`TYPO3\CMS\Extbase\DomainObject\AbstractEntity` instead and copy
the properties, getters and setters they need from the extbase classes.
Those copied properties may need database mapping entries, which can
be copied from :file:`EXT:extbase/Configuration/Extbase/Persistence/Classes.php`.

Extensions that use the extbase models directly should copy the class
to their extension namespace, ideally strip them down to what the extension
actually needs, and copy the needed mapping information from
:file:`EXT:extbase/Configuration/Extbase/Persistence/Classes.php`.

No database update of existing rows should be needed when transferring
the models to an own namespace, since none of the extbase models
configured a :php:`recordType` in the mapping file at
:file:`EXT:extbase/Configuration/Extbase/Persistence/Classes.php`.


.. index:: PHP-API, FullyScanned, ext:extbase
