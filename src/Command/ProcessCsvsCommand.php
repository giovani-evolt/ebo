<?php

namespace App\Command;

use App\Entity\Seller\Csv;
use App\Repository\Seller\CsvRepository;
use App\Service\IngestService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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
        #[Autowire(service: 's3.storage')]
        private FilesystemOperator $s3Storage
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        /*$this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute without making any changes')
        ;*/
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '2G');

        $io = new SymfonyStyle($input, $output);

        $this->entityManager->getConnection()->beginTransaction();

        try{
            $csvs = $this->csvRepository->loadToWorkBatch(10);

            foreach($csvs as $idx => $csv){

                $content = $this->s3Storage->read($csv->getFileWithSellerPath());

                $tmpPath = sys_get_temp_dir() . '/' . basename($csv->getFileWithSellerPath());

                file_put_contents(
                    $tmpPath, 
                    $this->cleanCsv($content, ['AmazonFees', 'FBAFees'])
                );
                
                // Start transaction for each CSV

                $result = $this->ingestService
                    ->setTmpFilePath($tmpPath)
                    ->ingestSettlement($csv);

                foreach($result['messages'] as $key => $messages){
                    $io->info(sprintf('%s %s' , $key,  $messages));
                }

                unlink($tmpPath);

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

                $this->entityManager->flush();
            }

            $this->entityManager->getConnection()->commit();
        }catch(\Exception $e){
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }

        return Command::SUCCESS;
    }

    protected function cleanCsv(string $csv, array $needles): string
    {
        // Normaliza saltos de línea (\r\n o \r → \n)
        $csv = str_replace(["\r\n", "\r"], "\n", $csv);

        $lines = explode("\n", $csv);
        $result = [];

        foreach ($lines as $line) {
            // Reemplazamos TABs por comas
            $line = str_replace("\t", ",", $line);

            foreach ($needles as $needle) {
                if ($needle !== '' && strpos($line, $needle) !== false) {
                    $line .= ",";   // Agregamos coma al final
                    break;          // No necesitamos seguir buscando
                }
            }

            $result[] = $line;
        }

        return implode("\n", $result);
    }

}
