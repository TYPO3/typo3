<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Definition\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Resources\Definition\RegistryInterface;

class ListCommand extends Command
{

    public function __construct(private readonly RegistryInterface $registry)
    {
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders([
            'Name',
            'Shortnames',
            'Api Version',
            'Kind'
        ]);
        foreach ($this->registry->findAll() as $resourceMetadata) {
            $table->addRow([
                $resourceMetadata->getNames()->getPlural(),
                implode(',', $resourceMetadata->getNames()->getShortnames()),
                implode(',', $resourceMetadata->getVersions()->getArrayCopy()),
                $resourceMetadata->getNames()->getKind()
                ]);
        }
        $table->render();
        return 0;
    }

}
