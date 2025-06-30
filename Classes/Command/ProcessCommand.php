<?php

declare(strict_types=1);

namespace WEBcoast\DeferredImageProcessing\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Process all entries in deferred image processing file list')
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'Limit the number of files to process [default=10]'
            )
            ->addOption(
                'status',
                null,
                InputArgument::OPTIONAL,
                'Show status'
            )
            ->addOption(
                'verbose',
                null,
                InputArgument::OPTIONAL,
                'Show status messages (only in combination with --status)'
            )
        ;
    }

    /**
     * Executes the command for showing sys_log entries
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int error code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->getArgument('limit') ?: 10;
        $status = $input->hasOption('status');
        $verbose = $input->hasOption('verbose');

        $io = new SymfonyStyle($input, $output);
        if ($status) {
            $io->title($this->getDescription());
            if ($verbose) {
                $io->info(sprintf('Processing up to %d records...', $limit));
            }
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_processedfile');
        $databaseRows = $queryBuilder
            ->select('uid', 'identifier', 'original', 'task_type', 'configuration')
            ->from('sys_file_processedfile')
            ->where($queryBuilder->expr()->eq('processed', $queryBuilder->createNamedParameter(false)))
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative()
        ;
        if ($status) {
            $io->createProgressBar();
            $io->progressStart(count($databaseRows));
        }

        foreach($databaseRows as $databaseRow) {
            if ($status && $verbose) {
                $io->info(sprintf('Processing file "%s"...', $databaseRow['identifier']));
            }
            try {
                $configuration = unserialize($databaseRow['configuration']);
                $configuration['deferred'] = true;

                $processedFile = $resourceFactory
                    ->getFileObject((int)$databaseRow['original'])
                    ->process($databaseRow['task_type'], $configuration)
                ;

                if ($processedFile->exists()) {
                    $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_processedfile');
                    $queryBuilder
                        ->update('sys_file_processedfile')
                        ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($databaseRow['uid'])))
                        ->set('processed', true)
                        ->executeStatement()
                    ;
                    if ($status && $verbose) {
                        $io->success(sprintf('File "%s" processed successfully.', $databaseRow['identifier']));
                    }
                }
            } catch (FileDoesNotExistException $e) {
                if ($status) {
                    $io->warning(sprintf('Skipping error "%s"', $e->getMessage()));
                }
            }

            if ($status) {
                $io->progressAdvance();
            }
        }

        if ($status) {
            $io->progressFinish();
            $io->writeln('Done.');
        }

        return Command::SUCCESS;
    }
}
