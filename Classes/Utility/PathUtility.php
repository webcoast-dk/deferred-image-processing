<?php

namespace WEBcoast\DeferredImageProcessing\Utility;

class PathUtility
{
    /**
     * Public URLs returned from TYPO3 are inconsistent.
     * Therefore, we need to strip an optional leading slash.
     *
     */
    public static function stripLeadingSlash(string $filename): string
    {
        return ltrim($filename, '/');
    }

}
