<?php

declare(strict_types=1);


namespace WEBcoast\DeferredImageProcessing\Event;


use TYPO3\CMS\Core\Resource\Processing\TaskInterface;

class ShouldDeferEvent
{
    protected TaskInterface $task;

    protected bool $shouldDefer;

    public function __construct(TaskInterface $task, bool $shouldDefer)
    {
        $this->task = $task;
        $this->shouldDefer = $shouldDefer;
    }

    public function getShouldDefer(): bool
    {
        return $this->shouldDefer;
    }

    public function setShouldDefer(bool $shouldDefer): void
    {
        $this->shouldDefer = $shouldDefer;
    }

    public function getTask(): TaskInterface
    {
        return $this->task;
    }
}
