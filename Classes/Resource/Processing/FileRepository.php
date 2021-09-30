<?php

namespace WEBcoast\DeferredImageProcessing\Resource\Processing;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileRepository
{
    const TABLE = 'tx_deferredimageprocessing_file';

    public static function hasProcessingInstructions(TaskInterface $task)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->count('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($task->getSourceFile()->getStorage()->getUid())),
                $queryBuilder->expr()->eq('source_file', $queryBuilder->createNamedParameter($task->getSourceFile()->getUid())),
                $queryBuilder->expr()->eq('task_type', $queryBuilder->createNamedParameter($task->getType())),
                $queryBuilder->expr()->eq('task_name', $queryBuilder->createNamedParameter($task->getName())),
                $queryBuilder->expr()->eq('checksum', $queryBuilder->createNamedParameter($task->getConfigurationChecksum()))
            );

        return $queryBuilder->execute()->fetchColumn(0) > 0;
    }

    public static function setProcessingInstructions(TaskInterface $task)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->insert(self::TABLE)
            ->values([
                'storage' => $queryBuilder->createNamedParameter($task->getSourceFile()->getStorage()->getUid() ,\PDO::PARAM_INT),
                'public_url' => $queryBuilder->createNamedParameter($task->getTargetFile()->getPublicUrl()),
                'source_file' => $queryBuilder->createNamedParameter($task->getSourceFile()->getUid(), \PDO::PARAM_INT),
                'task_type' => $queryBuilder->createNamedParameter($task->getType()),
                'task_name' => $queryBuilder->createNamedParameter($task->getName()),
                'configuration' => $queryBuilder->createNamedParameter(serialize($task->getConfiguration())),
                'checksum' => $queryBuilder->createNamedParameter($task->getConfigurationChecksum())
            ], false);

        return $queryBuilder->execute();
    }

    public static function getProcessingInstructionsByUrl($url)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->select('*')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->eq('public_url', $queryBuilder->createNamedParameter($url)));

        return $queryBuilder->execute()->fetch();
    }

    public static function updatePublicUrl(TaskInterface $task)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->update(self::TABLE)
            ->set('public_url', $task->getTargetFile()->getPublicUrl())
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($task->getSourceFile()->getStorage()->getUid())),
                $queryBuilder->expr()->eq('source_file', $queryBuilder->createNamedParameter($task->getSourceFile()->getUid())),
                $queryBuilder->expr()->eq('task_type', $queryBuilder->createNamedParameter($task->getType())),
                $queryBuilder->expr()->eq('task_name', $queryBuilder->createNamedParameter($task->getName())),
                $queryBuilder->expr()->eq('checksum', $queryBuilder->createNamedParameter($task->getConfigurationChecksum()))
            );
        $queryBuilder->execute();
    }

    public static function deleteProcessingInstructions($uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->delete(self::TABLE)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)));

        return $queryBuilder->execute();
    }
}
