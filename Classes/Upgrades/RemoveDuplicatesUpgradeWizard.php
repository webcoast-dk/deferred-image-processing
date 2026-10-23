<?php

declare(strict_types=1);

namespace WEBcoast\DeferredImageProcessing\Upgrades;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Up to 3.0.4, the middleware and the processing queue passed a `deferred` flag inside the processing
 * configuration. As the configuration is part of the processed file's lookup and file name, this created a second
 * `sys_file_processedfile` record per rendered image, and the queue rendered its images to file names nobody
 * references. This removes those records and their files, unless another record still uses the file.
 */
#[UpgradeWizard('deferredImageProcessing_removeDuplicates')]
class RemoveDuplicatesUpgradeWizard implements UpgradeWizardInterface
{
    protected const TABLE = 'sys_file_processedfile';

    protected const BATCH_SIZE = 500;

    public function getTitle(): string
    {
        return 'Deferred image processing: Remove duplicate processed file records';
    }

    public function getDescription(): string
    {
        return 'Removes the processed file records created with the "deferred" flag in their configuration by EXT:deferred_image_processing 3.0.4 and earlier, and their files unless another record still uses them. On large tables, run it on the command line.';
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
            ->where($queryBuilder->expr()->like('configuration', $this->deferredFlagPattern($queryBuilder)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnection();
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);

        $lastUid = 0;
        do {
            $queryBuilder = $connection->createQueryBuilder();
            $rows = $queryBuilder
                ->select('uid', 'storage', 'identifier')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->gt('uid', $queryBuilder->createNamedParameter($lastUid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->like('configuration', $this->deferredFlagPattern($queryBuilder))
                )
                ->orderBy('uid')
                ->setMaxResults(self::BATCH_SIZE)
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($rows as $row) {
                $lastUid = (int)$row['uid'];
                $identifier = (string)$row['identifier'];

                if ($identifier !== '' && !$this->isUsedByOtherRecord($connection, $row)) {
                    $storage = $storageRepository->findByUid((int)$row['storage']);
                    if ($storage !== null && $storage->hasFile($identifier)) {
                        // Deletes the file and, through core's FileDeletionAspect, the record
                        $processedFile = $processedFileRepository->findByUid($lastUid);
                        $processedFile->getStorage()->setEvaluatePermissions(false);
                        $processedFile->delete(true);
                    }
                }

                $connection->delete(self::TABLE, ['uid' => $lastUid]);
            }
        } while (count($rows) === self::BATCH_SIZE);

        return true;
    }

    protected function isUsedByOtherRecord(Connection $connection, array $row): bool
    {
        $queryBuilder = $connection->createQueryBuilder();

        return (bool)$queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter((int)$row['storage'], Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($row['identifier'])),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter((int)$row['uid'], Connection::PARAM_INT)),
                $queryBuilder->expr()->notLike('configuration', $this->deferredFlagPattern($queryBuilder))
            )
            ->executeQuery()
            ->fetchOne();
    }

    protected function deferredFlagPattern(QueryBuilder $queryBuilder): string
    {
        return $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards('s:8:"deferred";') . '%');
    }

    protected function getConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
    }
}
