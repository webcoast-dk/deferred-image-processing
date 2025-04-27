<?php

declare(strict_types=1);

namespace WEBcoast\DeferredImageProcessing\Resource\Processing;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

# https://api.typo3.org/11.5/_deferred_backend_image_processor_8php_source.html#l00034
class DeferredImageProcessor extends LocalImageProcessor
{
    public function canProcessTask(TaskInterface $task): bool
    {
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && !ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && $task->getType() === 'Image'
            && $task->getName() === 'CropScaleMask'
            && $task->getSourceFile()->getProperty('width') > 0
            && $task->getSourceFile()->getProperty('height') > 0
            && $task->getSourceFile()->getMimeType() !== 'image/svg+xml'
            && $task->getSourceFile()->getMimeType() !== 'application/pdf'
        ;
    }

    public function processTask(TaskInterface $task): void
    {
        if (!$this->shouldDefer($task)) {
            $configuration = $task->getConfiguration();
            unset($configuration['deferred']);

            $localTask = GeneralUtility::makeInstance(TaskTypeRegistry::class)->getTaskForType(
                $task->getType() . '.' . $task->getName(),
                $task->getTargetFile(),
                $configuration
            );

            parent::processTask($localTask);

            if ($localTask->isExecuted()) {
                $task->setExecuted($localTask->isSuccessful());
            }
            return;
        }

// TODO : check if needed ..
        if (!$this->canProcessTask($task)) {
            throw new \InvalidArgumentException(sprintf('Cannot process task of type "%s.%s"', $task->getType(), $task->getName()), 1350570621);
        }
        if ($this->checkForExistingTargetFile($task)) {
            return;
        }

        $configuration = $task->getConfiguration();
        if (isset($configuration['crop']) && !$configuration['crop'] instanceof Area) {
            // If `crop` is not an `Area`, it is most probably the original crop string from the file (e.g. from imageLinkWrap)
            // This is invalid and needs to be removed
            unset($configuration['crop']);
            // Create task with new configuration
            $task = GeneralUtility::makeInstance(TaskTypeRegistry::class)->getTaskForType(
                $task->getType() . '.' . $task->getName(),
                $task->getTargetFile(),
                $configuration
            );
        }

        $imageDimensions = ImageDimension::fromProcessingTask($task);
        if (!$task->getConfiguration()['crop']
        &&  $imageDimensions->getWidth() === $task->getTargetFile()->getOriginalFile()->getProperty('width')
        &&  $imageDimensions->getHeight() === $task->getTargetFile()->getOriginalFile()->getProperty('height')
        &&  $task->getTargetFile()->getExtension() === $task->getTargetFile()->getOriginalFile()->getExtension()
        ) {
            // If the target image dimensions are identical to the original file and no cropping is defined, do not process, but use the original file
            $task->setExecuted(true);// keep!
            $task->getTargetFile()->setUsesOriginalFile();

            return;
        }

        $task->setExecuted(true);// keep!
        $task->getTargetFile()->setName($task->getTargetFileName());
        $task->getTargetFile()->updateProperties([
            'width' => $imageDimensions->getWidth(),
            'height' => $imageDimensions->getHeight(),
            'checksum' => $task->getConfigurationChecksum()
        ]);
    }

    /**
     * Do not defer image rendering under the following circumstances:
     * * the processing is started from a deferred processing task
     * * the target file's storage is not public
     * * the deferred file processing has been disabled, e.g. inside the GIFBUILDER
     *
     * @param TaskInterface $task
     *
     * @return bool
     */
    protected function shouldDefer(TaskInterface $task): bool
    {
        if (isset($task->getConfiguration()['deferred'])) {
            return false;
        }

        if (!$task->getTargetFile()->getStorage()->isPublic()) {
            return false;
        }

        $context = GeneralUtility::makeInstance(Context::class);
        if ($context->hasAspect('fileProcessing') && !$context->getPropertyFromAspect('fileProcessing', 'deferProcessing')) {
            return false;
        }

        return true;#!$context->getAspect('backend.user')->isLoggedIn();
    }
}
