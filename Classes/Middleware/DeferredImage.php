<?php

declare(strict_types=1);

namespace WEBcoast\DeferredImageProcessing\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;

class DeferredImage extends \TYPO3\CMS\Core\Imaging\GraphicalFunctions implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $query = $request->getQueryParams();
        $match = $query['dip'] ?? null;

        if (!is_array($match)) {
            return $handler->handle($request);
        }

        if (!isset($match['chk'], $match['ext']) || !in_array($match['ext'], $this->webImageExt)) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction($request, '[510] DeferredImage');
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_processedfile');
        $databaseRow = $queryBuilder
            ->select('uid', 'identifier', 'original', 'task_type', 'configuration')
            ->from('sys_file_processedfile')
            ->where($queryBuilder->expr()->eq('checksum', $queryBuilder->createNamedParameter($match['chk'])))
            ->executeQuery()
            ->fetchAssociative()
        ;
        if (!$databaseRow) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction($request, '[404] DeferredImage');
        }

        // Add `deferred` to check at `DeferredImageProcessor`
        $configuration = unserialize($databaseRow['configuration']);
        $configuration['deferred'] = true;

        // Assign global TYPO3_REQUEST variable to make it available in the `DeferredImageProcessor`
        $GLOBALS['TYPO3_REQUEST'] = $request;

        # https://api.typo3.org/11.5/core_2_classes_2_resource_2_file_8php_source.html#l00252
        # https://api.typo3.org/11.5/_resource_storage_8php_source.html#l01428
        # https://api.typo3.org/11.5/_file_processing_service_8php_source.html#l00079
        # https://api.typo3.org/11.5/_processed_file_repository_8php_source.html#l00230
        $processedFile = GeneralUtility::makeInstance(ResourceFactory::class)
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
        } else {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(__CLASS__)
                ->error(sprintf('%s:%s could not be processed!', $databaseRow['uid'], $databaseRow['identifier']))
            ;
            $processedFile = $processedFile->getOriginalFile();
        }

        $response = GeneralUtility::makeInstance(ResponseFactoryInterface::class)
            ->createResponse()
            ->withStatus(200)
            ->withHeader('Content-Type', $processedFile->getMimeType())
            ->withHeader('Content-Length', (string)$processedFile->getSize())
            ;
        $response->getBody()->write($processedFile->getContents());

        return $response;
    }
}
