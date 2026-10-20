<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Exception\InvalidSiteConfigurationException;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Command for validating site configurations, reporting the errors of invalid sites
 */
#[AsCommand('site:validate', 'Validates the configuration of all sites or of a given site.')]
#[AsNonSchedulableCommand]
final class SiteValidateCommand extends Command
{
    public function __construct(private readonly SiteConfiguration $siteConfiguration)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'identifier',
            InputArgument::OPTIONAL,
            'The identifier of the site to validate. All sites are validated if omitted.',
            null,
            function (): array {
                return [
                    ...array_keys($this->siteConfiguration->getAllExistingSites()),
                    ...array_keys($this->siteConfiguration->getInvalidSites()),
                ];
            }
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        ['sites' => $sites, 'invalidSites' => $invalidSites] = $this->siteConfiguration->getSitesForValidation();
        $identifier = $input->getArgument('identifier');

        if ($identifier !== null) {
            if (!isset($sites[$identifier]) && !isset($invalidSites[$identifier])) {
                $io->error(sprintf('Site "%s" does not exist.', $identifier));
                return Command::FAILURE;
            }
            $invalidSites = array_intersect_key($invalidSites, [$identifier => true]);
            $sites = array_intersect_key($sites, [$identifier => true]);
        }

        $errorsBySite = [];
        foreach ($invalidSites as $invalidSite) {
            $errorsBySite[$invalidSite['identifier']][] = $invalidSite;
        }
        foreach ($sites as $site) {
            foreach ($this->validateBases($site) as $error) {
                $errorsBySite[$site->getIdentifier()][] = $error;
            }
        }

        if ($errorsBySite === []) {
            $io->success(match (true) {
                $identifier !== null => sprintf('Site "%s" is valid.', $identifier),
                count($sites) === 0 => 'No sites are configured.',
                count($sites) === 1 => 'The only configured site is valid.',
                default => sprintf('All %d sites are valid.', count($sites)),
            });
            return Command::SUCCESS;
        }

        $errorCount = 0;
        foreach ($errorsBySite as $siteIdentifier => $errors) {
            $io->section(sprintf('Site "%s"', $siteIdentifier));
            foreach ($errors as $error) {
                $io->definitionList(
                    ['Root page ID' => $error['rootPageId'] ?? '-'],
                    ['Configuration option' => $error['field'] ?? '-'],
                    ['Error' => OutputFormatter::escape($error['message'])],
                    ['Exception' => OutputFormatter::escape($error['type']) . ' (#' . $error['code'] . ')'],
                );
                $errorCount++;
            }
        }
        $io->error($errorCount === 1
            ? 'One site configuration error was found.'
            : sprintf('%d site configuration errors were found.', $errorCount));
        return Command::FAILURE;
    }

    /**
     * A Site object only validates the base of the base variant matching the current context,
     * which differs between deployment, CLI and web requests. Validate the base and the base of
     * every base variant on their own, for the site as well as for its languages.
     *
     * @return list<array{identifier: string, rootPageId: int, type: string, code: int, message: string, field: string}>
     */
    private function validateBases(Site $site): array
    {
        $configuration = $site->getConfiguration();
        $configurationWithoutVariants = $configuration;
        unset($configurationWithoutVariants['baseVariants']);
        foreach (array_keys($configurationWithoutVariants['languages'] ?? []) as $languageIndex) {
            unset($configurationWithoutVariants['languages'][$languageIndex]['baseVariants']);
        }
        // Each candidate replaces one base, an error of another option is reported by the candidate without variants
        $candidates = [[$configurationWithoutVariants, null, null]];
        foreach ($configuration['baseVariants'] ?? [] as $variantIndex => $baseVariant) {
            $candidate = $configurationWithoutVariants;
            $candidate['base'] = (string)($baseVariant['base'] ?? '');
            $candidates[] = [$candidate, 'base', 'baseVariants.' . $variantIndex . '.base'];
        }
        foreach ($configuration['languages'] ?? [] as $languageIndex => $languageConfiguration) {
            foreach ($languageConfiguration['baseVariants'] ?? [] as $variantIndex => $baseVariant) {
                $candidate = $configurationWithoutVariants;
                $candidate['languages'][$languageIndex]['base'] = (string)($baseVariant['base'] ?? '');
                $candidates[] = [
                    $candidate,
                    'languages.' . $languageIndex . '.base',
                    'languages.' . $languageIndex . '.baseVariants.' . $variantIndex . '.base',
                ];
            }
        }
        $errors = [];
        foreach ($candidates as [$candidate, $replacedPath, $variantPath]) {
            try {
                new Site($site->getIdentifier(), $site->getRootPageId(), $candidate, $site->getSettings());
            } catch (InvalidSiteConfigurationException $e) {
                if ($replacedPath !== null && $e->configurationPath !== $replacedPath) {
                    continue;
                }
                $cause = $e->getPrevious() ?? $e;
                $errors[] = [
                    'identifier' => $site->getIdentifier(),
                    'rootPageId' => $site->getRootPageId(),
                    'type' => $cause::class,
                    'code' => (int)$cause->getCode(),
                    'message' => $cause->getMessage(),
                    'field' => $variantPath ?? $e->configurationPath,
                ];
            }
        }
        return $errors;
    }
}
