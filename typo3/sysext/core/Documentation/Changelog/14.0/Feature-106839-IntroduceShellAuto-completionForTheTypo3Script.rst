..  include:: /Includes.rst.txt

..  _feature-106839-1749197278:

==========================================================================
Feature: #106839 - Introduce shell auto-completion for the `typo3` command
==========================================================================

See :issue:`106839`

Description
===========

A new CLI command :bash:`vendor/bin/typo3 completion` has been added to the
:shell:`typo3` CLI dispatcher script.

This command enables **shell auto-completion** for supported shells, allowing
developers to use the :kbd:`Tab` key to trigger command and option suggestions.

The :shell:`completion` command is provided by the
:composer:`symfony/console` package and is not a custom implementation.

This ensures compatibility with ongoing improvements in the Symfony ecosystem
and benefits from a broad user base and community support.

Supported shells
----------------

The command reports unsupported shells and lists available ones:

..  code-block:: bash

    # bin/typo3 completion shell
    Detected shell "shell", which is not supported by Symfony shell completion
    (supported shells: "bash", "fish", "zsh").

Installation modes
------------------

The command supports two installation modes — *static* and *dynamic*.
Run `vendor/bin/typo3 completion --help` to see detailed usage instructions and
the supported shells (bash, fish, zsh).

Static installation
~~~~~~~~~~~~~~~~~~~

Dump the completion script to a file and source it manually or install it
globally, for example:

..  code-block:: bash

    vendor/bin/typo3 completion bash | sudo tee /etc/bash_completion.d/typo3

Or dump the script to a local file and source it:

..  code-block:: bash

    bin/typo3 completion bash > completion.sh
    source completion.sh

To make it permanent, add the following line to your "~/.bashrc" file:

..  code-block:: bash
    :caption: ~/.bashrc

    source /path/to/completion.sh

Dynamic installation
~~~~~~~~~~~~~~~~~~~~

Add an `eval` line to your shell configuration file (for example `~/.bashrc`):

..  code-block:: bash

    eval "$(/var/www/html/vendor/bin/typo3 completion bash)"

Impact
======

The :shell:`typo3` CLI dispatcher now supports shell auto-completion, improving
the user experience without affecting existing command usage.
This also lays the foundation for further enhancements such as improved
auto-completion for command options and arguments.

The following existing commands already provide completion for their arguments:

*   :shell:`redirects:cleanup`
*   :shell:`redirects:checkintegrity`

Example
-------

The following example shows how to add auto-completion support to a custom
Symfony console command:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Command/GreetCommand.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Command;

    use Symfony\Component\Console\Attribute\AsCommand;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Completion\CompletionInput;
    use Symfony\Component\Console\Input\InputArgument;

    #[AsCommand(
        name: 'myextension:greet',
    )]
    class GreetCommand extends Command
    {
        protected function configure(): void
        {
            $this
                ->addArgument(
                    'names',
                    InputArgument::IS_ARRAY,
                    'Who do you want to greet (separate multiple names with a space)?',
                    null,
                    function (CompletionInput $input): array {
                        // Value already typed by the user, e.g. "myextension:greet Fa"
                        // before pressing Tab — this will contain "Fa"
                        $currentValue = $input->getCompletionValue();

                        // Example of available usernames
                        $availableUsernames = ['jane', 'jon'];

                        return $availableUsernames;
                    }
                );
        }
    }

For more details, see `Symfony Console Adding Argument Option Value Completion`_
and also for testing purpose see `Testing the Completion script`_.

..  _Symfony Console Adding Argument Option Value Completion: https://symfony.com/doc/7.2/console/input.html#adding-argument-option-value-completion
..  _Testing the Completion script: https://symfony.com/doc/7.2/console/input.html#testing-the-completion-script

..  index:: CLI, ext:core
