..  include:: /Includes.rst.txt

..  _feature-111374-1793279548:

================================================================
Feature: #111374 - Configuration module data on the command line
================================================================

See :issue:`111374`

Description
===========

The data of the :guilabel:`System > Configuration` backend module is now
available on the command line as :bash:`vendor/bin/typo3 lowlevel:inspect`. The
command reads the same providers as the module, including the ones extensions
register with the :yaml:`lowlevel.configuration.module.provider` service tag,
and needs no backend login. This helps over SSH, in deployment scripts, when
comparing environments and for AI coding agents working in a project.

..  code-block:: bash

    # All providers, grouped as in the backend module
    vendor/bin/typo3 lowlevel:inspect

    # One provider, narrowed down with one argument per key level
    vendor/bin/typo3 lowlevel:inspect tca tt_content columns header
    vendor/bin/typo3 lowlevel:inspect confVars SYS sitename

    # Complete output for scripts
    vendor/bin/typo3 lowlevel:inspect tca --flat | grep renderType
    vendor/bin/typo3 lowlevel:inspect httpMiddlewareStacks frontend --json

By default, the output is a tree: an overview of about 200 lines in which every
entry of the first level is listed, branches that don't fit are shown as
complete JSON on one line or as a shortened preview, and a closing hint says
what was cut. :bash:`--depth` shows a fixed number of levels instead,
:bash:`--depth=0` everything. :bash:`--flat` writes one line per value with its
path, which can be pasted back as arguments, and :bash:`--json` writes JSON.
Both are always complete and are refused above 1 MB unless
:bash:`--no-size-limit` is given.

:bash:`--resolve-labels` translates label references such as
:php:`'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header'`
into English, :bash:`--resolve-labels=de` into another installed language. In
the tree, the original reference follows as a comment.

Errors are written to stderr. The command exits with code 2 for invalid input,
such as an unknown provider or key, and with code 1 if a provider cannot be
read on the command line or the output is too large. Provider identifiers,
keys and languages are offered by the shell completion.

Options blinded in the module, such as the database credentials and the
encryption key, are blinded on the command line as well.

Impact
======

Integrators, developers, scripts and AI agents can inspect the configuration,
TCA, event listeners, middlewares, routes, backend modules, site sets and the
other data of the Configuration module without logging in to the backend.

Listeners to
:php:`\TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent` are now
also called on the command line, where there is no request and no
authenticated backend user. A listener that blinds options must not depend on
either, or the command shows options that the module hides.

..  index:: CLI, ext:lowlevel
