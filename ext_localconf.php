<?php

defined('TYPO3') || die('Access denied! Cannot run outside of TYPO3 context!');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'][WEBcoast\DeferredImageProcessing\Resource\Processing\DeferredImageProcessor::class] = [
    'className' => WEBcoast\DeferredImageProcessing\Resource\Processing\DeferredImageProcessor::class,
    'before' => ['LocalImageProcessor'],
];
