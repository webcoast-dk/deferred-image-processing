<?php

namespace WEBcoast\DeferredImageProcessing\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WEBcoast\DeferredImageProcessing\Resource\Processing\FileRepository;

class ProcessCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Process all entries in deferred image processing file list')
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'Limit the number of files to process. Defaults to 10'
            );
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
        $verbose = $input->hasOption('verbose') && $input->getOption('verbose');
        $quiet = $input->hasOption('quiet') && $input->getOption('quiet');

        $io = new SymfonyStyle($input, $output);
        if (!$quiet) {
            $io->title($this->getDescription());
            if ($verbose) {
                $io->info(sprintf('Processing up to %d records...', $limit));
            }
        }

        $processingInstructionsResults = FileRepository::getProcessingInstructions($limit);
        if (!$quiet) {
            $io->createProgressBar();
            $io->progressStart(count($processingInstructionsResults));
        }
        foreach ($processingInstructionsResults as $processingInstructions) {
            if (!$quiet && $verbose) {
                $io->info(sprintf('Processing file "%s"...', $processingInstructions['public_url']));
            }
            try {
                $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($processingInstructions['storage']);
                $configuration = unserialize($processingInstructions['configuration']);
                $configuration['deferred'] = true;
                $processedFile = $storage->processFile(
                    GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($processingInstructions['source_file']),
                    $processingInstructions['task_type'] . '.' . $processingInstructions['task_name'],
                    $configuration
                );

                if ($processedFile->exists()) {
                    FileRepository::deleteProcessingInstructions($processingInstructions['uid']);
                    if (!$quiet && $verbose) {
                        $io->success(sprintf('File "%s" processed successfully.', $processingInstructions['public_url']));
                    }
                }
            } catch (FileDoesNotExistException $e) {
                if (!$quiet) {
                    $io->warning(sprintf('Skipping error "%s"', $e->getMessage()));
                }
                FileRepository::deleteProcessingInstructions($processingInstructions['uid']);
            }

            if (!$quiet) {
                $io->progressAdvance();
            }
        }

        if (!$quiet) {
            $io->progressFinish();
            $io->writeln('Done.');
        }

        return Command::SUCCESS;
    }
}
