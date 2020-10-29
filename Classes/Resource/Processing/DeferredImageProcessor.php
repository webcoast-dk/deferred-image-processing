<?php

namespace WEBcoast\DeferredImageProcessing\Resource\Processing;

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DeferredImageProcessor extends LocalImageProcessor
{
    public function canProcessTask(TaskInterface $task)
    {
        return $task->getType() === 'Image'
            && $task->getName() === 'CropScaleMask'
            && $task->getSourceFile()->getMimeType() !== 'image/svg+xml'
            && $task->getSourceFile()->getExtension() !== 'svg';
    }

    public function processTask(TaskInterface $task)
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

            if (!FileRepository::hasProcessingInstructions($task)) {
                FileRepository::setProcessingInstructions($task);
            }

            $task->setExecuted(true);
            $imageDimensions = $this->getTargetDimensions($task);
            $task->getTargetFile()->setName($task->getTargetFileName());
            $task->getTargetFile()->updateProperties(
                ['width' => $imageDimensions[0], 'height' => $imageDimensions[1], 'checksum' => $task->getConfigurationChecksum()]
            );
        }
    }

    protected function getTargetDimensions(TaskInterface $task)
    {
        $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $originalFileInfo = $graphicalFunctions->getImageDimensions($task->getSourceFile()->getForLocalProcessing());
        $configuration = $task->getConfiguration();

        return $graphicalFunctions->getImageScale($originalFileInfo, $configuration['width'], $configuration['height'], $configuration);
    }
}
