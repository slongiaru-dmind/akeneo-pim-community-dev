<?php

declare(strict_types=1);

namespace Akeneo\Platform\Installer\Application\InstallPim;

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class InstallPimCommand
{
    public function __construct(
        public readonly bool $force,
        public readonly bool $dropDatabase,
    ) {
    }
}
