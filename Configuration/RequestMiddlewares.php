<?php

return [
    'frontend' => [
        'webcoast/deferred-image-processing/image-processor' => [
            'target' => WEBcoast\DeferredImageProcessing\Middleware\ImageProcessor::class,
            'before' => [
                'typo3/cms-frontend/page-resolver'
            ],
            'after' => [
                'typo3/cms-frontend/static-route-resolver'
            ]
        ]
    ]
];
