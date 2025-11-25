<?php

namespace App\Command;

use App\Entity\Seller\Csv;
use App\Repository\Seller\CsvRepository;
use App\Service\IngestService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'evolt:process-csvs',
    description: 'Process pending csv files uploaded by sellers',
)]
class ProcessCsvsCommand extends Command
{

    public function __construct(
        private CsvRepository $csvRepository,
        private IngestService $ingestService,
        private EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute without making any changes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Processing without making any changes');
        }

        $csvs = $this->csvRepository->loadToWorkBatch();

        foreach($csvs as $idx => $csv){

            $result = $this->ingestService
                ->ingestSettlement($csv);

            if(count($result['messages']) > 0){
                $csv
                    ->setStatus(Csv::STATUS_WITH_ERRORS)
                    ->setMessages($result['messages'])
                ;

                $io->info(sprintf('Processed CSV %s with %d settlements with errors' , $csv->getFileWithSellerPath(), count($result['settlements'])));
                $io->error(sprintf('messages: %s' , implode("\n", $result['messages'])));
            } else {
                $csv->setStatus(Csv::STATUS_DONE);
                $io->success(sprintf('Processed CSV %s with %d settlements' , $csv->getFileWithSellerPath(), count($result['settlements'])));
            }
            // $this->entityManager->persist($csv);
        }

        $this->entityManager->flush();

        // $this->entityManager->getConnection()->commit();

        return Command::SUCCESS;
    }
}
