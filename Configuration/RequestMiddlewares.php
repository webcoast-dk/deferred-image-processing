<?php

declare(strict_types=1);

return [
    'frontend' => [
        'webcoast/deferred-image-processing/image-processor' => [
            'target' => \WEBcoast\DeferredImageProcessing\Middleware\DeferredImage::class,
            'after' => [
                'typo3/cms-frontend/maintenance-mode'
            ],
            'before' => [
                'typo3/cms-frontend/backend-user-authentication'
            ]
        ]
    ]
];
