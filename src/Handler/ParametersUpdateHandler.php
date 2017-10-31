<?php

namespace EvozonPhp\ComposerUtilities\Handler;

use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Synchronize source to target json files.
 *
 * @copyright Evozon Systems SRL (http://www.evozon.com/)
 * @author    Constantin Bejenaru <constantin.bejenaru@evozon.com>
 */
class ParametersUpdateHandler extends AbstractHandler
{
    const DEFAULT_SOURCE_FILE = 'app/config/parameters.yml.dist';
    const DEFAULT_TARGET_FILE = 'app/config/parameters.yml';

    const PATH_FILE = '[parameters-update][file]';
    const PATH_FILE_DIST = '[parameters-update][dist-file]';

    /**
     * PropertyAccess path for update parameters node.
     *
     * @var string
     */
    const PATH_PARAMETERS_UPDATE = '[parameters-update][parameters]';

    /**
     * Key name holding parameters to be updated.
     */
    const KEY_PARAMETERS = 'parameters';

    /**
     * Update parameters.
     *
     * @param string $source
     * @param string $target
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function handle(string $source, string $target): array
    {
        $filesystem = new Filesystem();

        if (!$filesystem->exists($source)) {
            throw new InvalidArgumentException(sprintf('Source file %s does not exist!', $source));
        }

        if (!$filesystem->exists($target)) {
            throw new InvalidArgumentException(sprintf('Target file %s does not exist!', $target));
        }

        // parameters.yml.dist
        $sourceValues = Yaml::parse(file_get_contents($source));
        if (!isset($sourceValues[self::KEY_PARAMETERS]) || !is_array($sourceValues[self::KEY_PARAMETERS])) {
            throw new InvalidArgumentException(
                sprintf('The top-level key %s is missing or invalid in source %s.', self::KEY_PARAMETERS, $source)
            );
        }

        // parameters.yml
        $targetValues = Yaml::parse(file_get_contents($target));
        if (!isset($targetValues[self::KEY_PARAMETERS]) || !is_array($targetValues[self::KEY_PARAMETERS])) {
            throw new InvalidArgumentException(
                sprintf('The top-level key %s is missing or invalid in target %s.', self::KEY_PARAMETERS, $target)
            );
        }

        return $this->updateParameters($sourceValues, $targetValues);
    }

    /**
     * Update parameters.
     *
     * @param array $source
     * @param array $target
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function updateParameters(array $source, array $target): array
    {
        foreach ($this->getUpdateParameters() as $parameter) {
            if (!isset($source[self::KEY_PARAMETERS][$parameter])) {
                $this->io->write(sprintf('Parameter <info>%s</info> does not exist in source.', $parameter));
                continue;
            }

            if (!isset($target[self::KEY_PARAMETERS][$parameter])) {
                $this->io->write(sprintf('Parameter <info>%s</info> does not exist in target.', $parameter));
                continue;
            }

            $update = $this->io->askConfirmation(
                sprintf('Do you want to update parameter <info>%s</info>? (y/n)', $parameter),
                true
            );

            if (!$update) {
                continue;
            }

            // Property accessor path (i.e.: [parameters][foo])
            $path = sprintf('[%s][%s]', self::KEY_PARAMETERS, $parameter);

            $this->getAccessor()->setValue(
                $target,
                $path,
                $this->getAccessor()->getValue($source, $path)
            );
        }

        return $target;
    }

    /**
     * Get update parameters.
     *
     * @return array
     */
    private function getUpdateParameters(): array
    {
        return $this->getAccessor()->isReadable($this->getConfig(), self::PATH_PARAMETERS_UPDATE) ?
            $this->getAccessor()->getValue($this->getConfig(), self::PATH_PARAMETERS_UPDATE) :
            [];
    }
}
