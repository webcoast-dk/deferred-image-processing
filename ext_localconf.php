<?php

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'][WEBcoast\DeferredImageProcessing\Resource\Processing\DeferredImageProcessor::class] = [
    'className' => WEBcoast\DeferredImageProcessing\Resource\Processing\DeferredImageProcessor::class,
    'before' => ['LocalImageProcessor'],
];
