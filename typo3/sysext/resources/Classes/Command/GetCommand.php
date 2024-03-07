<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Resources\Definition\RegistryInterface;
use TYPO3\CMS\Resources\Domain\ResourceUri;
use TYPO3\CMS\Resources\Message\ResourceRequest;
use TYPO3\CMS\Resources\ResourceInterface;
use TYPO3\CMS\Resources\ResourceServer;

class GetCommand extends Command
{

    public function __construct(private readonly RegistryInterface $registry, private readonly ResourceServer $resourceServer)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('type', InputArgument::REQUIRED);
        $this->addArgument('id', InputArgument::OPTIONAL);
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->resourceServer->handle(new ResourceRequest(new ResourceUri('t3://resourcedefinitions.resources.typo3.org/v1')));
        $table = new Table($output);
        $table->setHeaders([
            'Id',
        ]);
        /** @var ResourceInterface $item */
        foreach ((array)$response->getBodyObject() as $item) {
            $table->addRow([$item->getId()]);
        }
        $table->render();
        return 0;
    }
}
