<?php

declare(strict_types=1);

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Akeneo\Platform\Installer\Infrastructure\FixtureLoader;

use Akeneo\Platform\Bundle\InstallerBundle\FixtureLoader\FixturePathProvider;
use Akeneo\Platform\Installer\Domain\FixtureLoad\FixturePathResolver;
use Akeneo\Platform\Installer\Domain\FixtureLoader\JobInstanceConfiguratorInterface;
use Akeneo\Tool\Component\Batch\Model\JobInstance;

/**
 * Configure the job instances that are used to install the PIM by setting the relevant file path for each job.
 *
 * In case of standard install, the file paths can be fetched from the application configuration (installer_data).
 *
 * In case of behat install, this configurator can also be used with a list of paths to use.
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 */
class JobInstancesConfigurator implements JobInstanceConfiguratorInterface
{
    public function __construct(
        private readonly array $bundles
    ) {}

    /**
     * The standard method to configure job instances with files provided in an install fixtures set
     *
     * @throws \Exception
     * @return JobInstance[]
     */
    public function configureJobInstancesWithInstallerData(string $catalogPath, array $jobInstances): array
    {
        $installerDataPath = FixturePathResolver::resolve($catalogPath, $this->bundles);
        if (!is_dir($installerDataPath)) {
            throw new \Exception(sprintf('Path "%s" not found', $installerDataPath));
        }

        $configuredJobInstances = [];
        foreach ($jobInstances as $jobInstance) {
            $configuration = $jobInstance->getRawParameters();

            $configuration['storage']['file_path'] = sprintf('%s%s', $installerDataPath, $configuration['storage']['file_path']);
            if (!is_readable($configuration['storage']['file_path'])) {
                throw new \Exception(
                    sprintf(
                        'The job "%s" can\'t be processed because the file "%s" is not readable',
                        $jobInstance->getCode(),
                        $configuration['storage']['file_path']
                    )
                );
            }
            $jobInstance->setRawParameters($configuration);
            $configuredJobInstances[] = $jobInstance;
        }

        return $configuredJobInstances;
    }

    /**
     * An alternative methods with configure job instance with replacement paths, please note that we can configure
     * here several job instances for a same job, for instance loading users.csv with a Community Edition file and
     * with an Enterprise Edition file
     *
     * @param JobInstance[] $jobInstances
     * @param array $replacePaths
     * @throws \Exception
     * @return JobInstance[]
     */
    public function configureJobInstancesWithReplacementPaths(array $jobInstances, array $replacePaths): array
    {
        $counter = 0;

        $configuredJobInstances = [];
        foreach ($jobInstances as $jobInstance) {
            $configuration = $jobInstance->getRawParameters();

            if (!isset($replacePaths[$configuration['storage']['file_path']])) {
                throw new \Exception(sprintf('No replacement path for "%s"', $configuration['storage']['file_path']));
            }
            foreach ($replacePaths[$configuration['storage']['file_path']] as $replacePath) {
                $configuredJobInstance = clone $jobInstance;
                $configuredJobInstance->setCode($configuredJobInstance->getCode().''.$counter++);
                $configuration['storage']['file_path'] = $replacePath;
                if (!is_readable($configuration['storage']['file_path'])) {
                    throw new \Exception(
                        sprintf(
                            'The job "%s" can\'t be processed because the file "%s" is not readable',
                            $configuredJobInstance->getCode(),
                            $configuration['storage']['file_path']
                        )
                    );
                }
                $configuredJobInstance->setRawParameters($configuration);
                $configuredJobInstances[] = $configuredJobInstance;
            }
        }

        return $configuredJobInstances;
    }
}
