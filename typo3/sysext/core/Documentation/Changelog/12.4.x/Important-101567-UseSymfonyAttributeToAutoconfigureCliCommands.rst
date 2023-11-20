.. include:: /Includes.rst.txt

.. _important-101567-1691227840:

========================================================================
Important: #101567 - Use Symfony attribute to autoconfigure cli commands
========================================================================

See :issue:`101567`

Description
===========

The symfony PHP attribute :php:`\Symfony\Component\Console\Attribute\AsCommand`
is now accepted to register console commands.
This way CLI commands can be registered by setting the attribute on the command
class. Only the parameters `command`, `description`, `aliases` and `hidden` are
still viable. In order to overwrite the schedulable parameter use the old
:file:`Services.yaml` way to register console commands. By default `schedulable`
is true.

Before:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Command\MyCommand:
      tags:
        - name: 'console.command'
          command: 'myprefix:dofoo'
          description: 'My description'
          schedulable: true
        - name: 'console.command'
          command: 'myprefix:dofoo-alias'
          alias: true

After:

The registration can be removed from the :file:`Services.yaml` file and the
attribute is assigned to the command class instead:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Command/MyCommand.php

    <?php

    namespace MyVendor\MyExtension\Command;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Attribute\AsCommand;

    #[AsCommand(name: 'myprefix:dofoo', description: 'My description', aliases: ['myprefix:dofoo-alias'])]
    class MyCommand extends Command
    {
    }


Impact
======

The registration of cli commands is simplified that way.
When using this attribute there is no need to register the command in the
:file:`Services.yaml` file. Existing configurations work as before.

.. index:: Backend, CLI, PHP-API, ext:core
