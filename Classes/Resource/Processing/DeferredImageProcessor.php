<?php

namespace WEBcoast\DeferredImageProcessing\Resource\Processing;

use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DeferredImageProcessor extends LocalImageProcessor
{
    public function canProcessTask(TaskInterface $task): bool
    {
        return $task->getType() === 'Image'
            && $task->getName() === 'CropScaleMask'
            && $task->getSourceFile()->getMimeType() !== 'image/svg+xml'
            && $task->getSourceFile()->getExtension() !== 'svg';
    }

    public function processTask(TaskInterface $task): void
    {
        if (isset($task->getConfiguration()['deferred'])) {
            $configuration = $task->getConfiguration();
            unset($configuration['deferred']);
            $localTask = GeneralUtility::makeInstance(TaskTypeRegistry::class)->getTaskForType($task->getType() . '.' . $task->getName(), $task->getTargetFile(), $configuration);

            parent::processTask($localTask);

            if ($localTask->isExecuted()) {
                $task->setExecuted($localTask->isSuccessful());
            }
        } else {
            if (!$this->canProcessTask($task)) {
                throw new \InvalidArgumentException('Cannot process task of type "' . $task->getType() . '.' . $task->getName() . '"', 1350570621);
            }
            if ($this->checkForExistingTargetFile($task)) {
                return;
            }

            $imageDimensions = ImageDimension::fromProcessingTask($task);
            if ($imageDimensions->getWidth() === $task->getTargetFile()->getOriginalFile()->getProperty('width') && $imageDimensions->getHeight() === $task->getTargetFile()->getOriginalFile()->getProperty('height')) {
                // If the target image dimensions are identical to the original file, do not process, but use the original file
                $task->setExecuted(true);
                $task->getTargetFile()->setUsesOriginalFile();
            } else {
                if (!FileRepository::hasProcessingInstructions($task)) {
                    // If we got an empty processed file (not persisted yet), set the name
                    // so we can get the public url of the processed image.
                    if (!$task->getTargetFile()->isPersisted()) {
                        $task->getTargetFile()->setName($task->getTargetFileName());
                    }
                    FileRepository::setProcessingInstructions($task);
                }

                $task->setExecuted(true);
                $task->getTargetFile()->setName($task->getTargetFileName());
                $task->getTargetFile()->updateProperties(
                    ['width' => $imageDimensions->getWidth(), 'height' => $imageDimensions->getHeight(), 'checksum' => $task->getConfigurationChecksum()]
                );
            }
        }
    }
}
