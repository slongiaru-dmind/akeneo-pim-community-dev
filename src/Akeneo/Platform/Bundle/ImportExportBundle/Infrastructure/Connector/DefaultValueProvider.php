<?php

declare(strict_types=1);

/*
 * This file is part of the Akeneo PIM Enterprise Edition.
 *
 * (c) 2022 Akeneo SAS (https://www.akeneo.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Akeneo\Platform\Bundle\ImportExportBundle\Infrastructure\Connector;

use Akeneo\Tool\Component\Batch\Job\JobInterface;
use Akeneo\Tool\Component\Batch\Job\JobParameters\DefaultValuesProviderInterface;

class DefaultValueProvider implements DefaultValuesProviderInterface
{
    public function __construct(
        private DefaultValuesProviderInterface $overriddenProvider,
        /** @var string[] */
        private array $supportedJobNames,
    ) {
    }

    /**
     * @return string[]
     */
    public function getDefaultValues(): array
    {
        $defaultValues = $this->overriddenProvider->getDefaultValues();
        $defaultValues['storage'] = [
            'type' => 'none',
        ];

        return $defaultValues;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(JobInterface $job): bool
    {
        return in_array($job->getName(), $this->supportedJobNames);
    }
}
