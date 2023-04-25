.. include:: /Includes.rst.txt

.. _deprecation-100349-1680097287:

========================================================================
Deprecation: #100349 - TypoScript loginUser() and usergroup() conditions
========================================================================

See :issue:`100349`

Description
===========

The two TypoScript / TSconfig related condition functions
:typoscript:`[loginUser()]` and :typoscript:`[usergroup()]` have
been marked as deprecated with TYPO3 v12, should not be used anymore
and will be removed in TYPO3 v13. They can be substituted using
conditions based on the variables :typoscript:`frontend.user` and
:typoscript:`backend.user`.


Impact
======

Using the old conditions in frontend TypoScript or TSconfig triggers a
deprecation level log entry in TYPO3 v12 and will stop working with
TYPO3 v13.


Affected installations
======================

Instances with TypoScript or TSconfig using one of the above functions
may be affected. This is a relatively common use case, but affected
instances can be adapted quite easily.


Migration
=========

There is a rather straightforward migration path. In general, switch to
either :typoscript:`frontend.user` to test for frontend user state
(available in frontend TypoScript), or to :typoscript:`backend.user` (available
in frontend TypoScript and TSconfig).

Note the transition can be done in existing TYPO3 v11 projects already.

Some examples:

.. code-block:: typoscript

    [loginUser('*')]
        page = PAGE
        page.20 = TEXT
        page.20.value = User is logged in<br />
    [end]
    [frontend.user.isLoggedIn]
        page = PAGE
        page.21 = TEXT
        page.21.value = User is logged in<br />
    [end]

    [loginUser('*') === false]
        page = PAGE
        page.30 = TEXT
        page.30.value = User is not logged in<br />
    [end]
    [!frontend.user.isLoggedIn]
        page = PAGE
        page.31 = TEXT
        page.31.value = User is not not logged in<br />
    [end]

    [loginUser(13)]
        page = PAGE
        page.40 = TEXT
        page.40.value = Frontend user has the uid 13<br />
    [end]
    [frontend.user.userId == 13]
        page = PAGE
        page.41 = TEXT
        page.41.value = Frontend user has the uid 13<br />
    [end]

    [loginUser('1,13')]
        page = PAGE
        page.50 = TEXT
        page.50.value = Frontend user uid is 1 or 13<br />
    [end]
    [frontend.user.userId in [1,13]]
        page = PAGE
        page.51 = TEXT
        page.51.value = Frontend user uid is 1 or 13<br />
    [end]

    [usergroup('*')]
        page = PAGE
        page.60 = TEXT
        page.60.value = A Frontend user is logged in and belongs to some usergroup.<br />
    [end]
    # Prefer [frontend.user.isLoggedIn] to not rely on magic array values.
    [frontend.user.userGroupIds !== [0, -1]]
        page = PAGE
        page.61 = TEXT
        page.61.value = A Frontend user is logged in and belongs to some usergroup.<br />
    [end]

    [usergroup(11)]
        page = PAGE
        page.70 = TEXT
        page.70.value = Frontend user is member of group with uid 11<br />
    [end]
    [11 in frontend.user.userGroupIds]
        page = PAGE
        page.71 = TEXT
        page.71.value = Frontend user is member of group with uid 11<br />
    [end]

    [usergroup('1,11')]
        page = PAGE
        page.80 = TEXT
        page.80.value = Frontend user is member of group 1 or 11<br />
    [end]
    [1 in frontend.user.userGroupIds || 11 in frontend.user.userGroupIds]
        page = PAGE
        page.81 = TEXT
        page.81.value = Frontend user is member of group 1 or 11<br />
    [end]


.. index:: TSConfig, TypoScript, NotScanned, ext:core
