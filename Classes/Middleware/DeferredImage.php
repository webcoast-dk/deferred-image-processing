<?php

declare(strict_types=1);

namespace WEBcoast\DeferredImageProcessing\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;

class DeferredImage extends GraphicalFunctions implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $query = $request->getQueryParams();
        $match = $query['dip'] ?? null;

        if (!is_array($match)) {
            return $handler->handle($request);
        }

        if (!isset($match['chk'], $match['ext']) || !$this->isImageExtensionSupported($match['ext'])) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction($request, '[510] DeferredImage');
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_processedfile');
        $databaseRow = $queryBuilder
            ->select('uid', 'identifier', 'original', 'task_type', 'configuration')
            ->from('sys_file_processedfile')
            ->where($queryBuilder->expr()->eq('checksum', $queryBuilder->createNamedParameter($match['chk'])))
            ->executeQuery()
            ->fetchAssociative();

        if (!$databaseRow) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction($request, '[404] DeferredImage');
        }

        // Add `deferred` to check at `DeferredImageProcessor`
        $configuration = unserialize($databaseRow['configuration']);
        $configuration['deferred'] = true;

        // Assign global TYPO3_REQUEST variable to make it available in the `DeferredImageProcessor`
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $processedFile = GeneralUtility::makeInstance(ResourceFactory::class)
            ->getFileObject((int) $databaseRow['original'])
            ->process($databaseRow['task_type'], $configuration);

        if ($processedFile->exists()) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_processedfile');
            $queryBuilder
                ->update('sys_file_processedfile')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($databaseRow['uid'])))
                ->set('processed', true)
                ->executeStatement();
        } else {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(__CLASS__)
                ->error(sprintf('%s:%s could not be processed!', $databaseRow['uid'], $databaseRow['identifier']));
            $processedFile = $processedFile->getOriginalFile();
        }

        $response = GeneralUtility::makeInstance(ResponseFactoryInterface::class)
            ->createResponse()
            ->withStatus(200)
            ->withHeader('Content-Type', $processedFile->getMimeType())
            ->withHeader('Content-Length', (string) $processedFile->getSize());
        $response->getBody()->write($processedFile->getContents());

        return $response;
    }

    protected function isImageExtensionSupported(string $ext): bool
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        if ($typo3Version < 13) {
            return in_array($ext, $this->webImageExt);
        }

        return ($ext === 'avif' && $this->avifSupportAvailable()) || in_array($ext, $this->webImageExt);
    }
}
