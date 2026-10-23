<?php

declare(strict_types=1);

namespace WEBcoast\DeferredImageProcessing\Upgrades;

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Up to 3.0.4, the `processed` column defaulted to 0, which put every processed file record into the processing
 * queue, also the ones that existed before the installation or were never deferred. This removes the records whose
 * file exists or which use the original file from the queue again.
 */
#[UpgradeWizard('deferredImageProcessing_dequeueExistingFiles')]
class DequeueExistingFilesUpgradeWizard implements UpgradeWizardInterface
{
    protected const TABLE = 'sys_file_processedfile';

    protected const BATCH_SIZE = 1000;

    public function getTitle(): string
    {
        return 'Deferred image processing: Remove existing processed files from the processing queue';
    }

    public function getDescription(): string
    {
        return 'Removes processed file records whose file already exists, or which use the original file, from the processing queue of EXT:deferred_image_processing. On large tables, run it on the command line.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder();

        return (bool)$queryBuilder
            ->select('uid')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->eq('processed', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnection();
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);

        $lastUid = 0;
        do {
            $queryBuilder = $connection->createQueryBuilder();
            $rows = $queryBuilder
                ->select('uid', 'storage', 'identifier')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->gt('uid', $queryBuilder->createNamedParameter($lastUid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('processed', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
                ->orderBy('uid')
                ->setMaxResults(self::BATCH_SIZE)
                ->executeQuery()
                ->fetchAllAssociative();

            $uids = [];
            foreach ($rows as $row) {
                $lastUid = (int)$row['uid'];
                $identifier = (string)$row['identifier'];
                $storage = $storageRepository->findByUid((int)$row['storage']);
                if ($identifier === '' || ($storage !== null && $storage->hasFile($identifier))) {
                    $uids[] = $lastUid;
                }
            }

            if ($uids !== []) {
                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder
                    ->update(self::TABLE)
                    ->set('processed', null)
                    ->where($queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($uids, ArrayParameterType::INTEGER)))
                    ->executeStatement();
            }
        } while (count($rows) === self::BATCH_SIZE);

        return true;
    }

    protected function getConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
    }
}
