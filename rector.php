<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip(
        [
            __DIR__ . '/tests/_craft',
            __DIR__ . '/tests/_output',
            __DIR__ . '/tests/_support',
            __DIR__ . '/src/translations',

        ]
    )
    ->withPhpSets(php80: true)
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::EARLY_RETURN
    ]);
