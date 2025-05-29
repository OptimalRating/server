<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    // Paths Rector should scan
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/routes',
    ]);

    // Rules or sets to apply
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
    ]);
};
