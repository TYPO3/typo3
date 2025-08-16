..  include:: /Includes.rst.txt

..  _feature-108229-1763675198:

=====================================
Feature: #108229 - Fluid cache warmup
=====================================

See :issue:`108229`

Description
===========

TYPO3 v14 leverages the revamped Fluid v5 warmup feature and integrates
a fluid template warmup directly into CLI command :shell:`typo3 cache:warmup`.

The command finds and compiles all :file:`*.fluid.*` (example: :file:`Index.fluid.html`)
files found in extensions.

Fluid warmup can also be called directly using :shell:`typo3 fluid:cache:warmup`
which will additionally output compile time deprecations found within fluid
template files.


Impact
======

The warmup command can be useful to reduce ramp up time after deployments.

..  index:: CLI, Fluid, ext:fluid
