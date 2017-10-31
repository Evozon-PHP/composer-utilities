<?php

namespace EvozonPhp\ComposerUtilities\Handler;

use Composer\Json\JsonFile;
use Composer\Repository\PlatformRepository;

/**
 * Synchronize source to target json files.
 *
 * @copyright Evozon Systems SRL (http://www.evozon.com/)
 * @author    Constantin Bejenaru <constantin.bejenaru@evozon.com>
 */
class SyncJsonHandler extends AbstractHandler
{
    /**
     * Default target composer file name.
     *
     * @var string
     */
    const DEFAULT_TARGET_COMPOSER_FILE = 'composer.json';

    /**
     * Default source composer file name.
     *
     * @var string
     */
    const DEFAULT_SOURCE_COMPOSER_FILE = 'dev.json';

    /**
     * PropertyAccess path for sync ingore nodes.
     *
     * @var string
     */
    const PATH_SYNC_IGNORE_NODES = '[sync][ignore][nodes]';

    /**
     * PropertyAccess path for packages node.
     *
     * @var string
     */
    const PATH_REQUIRE = '[require]';

    /**
     * PropertyAccess path for development packages nodes.
     *
     * @var string
     */
    const PATH_REQUIRE_DEV = '[require-dev]';

    /**
     * Synchronize source to target.
     *
     * @param JsonFile $source
     * @param JsonFile $target
     *
     * @return array
     */
    public function handle(JsonFile $source, JsonFile $target): array
    {
        $sourceData = $source->read();
        $targetData = $target->read();

        $result = $sourceData;

        foreach ($this->getIgnoreNodes() as $node) {
            $value = $this->getAccessor()->getValue($targetData, $node);
            if ($value) {
                $this->getAccessor()->setValue($result, $node, $value);
            }
        }

        $packages = $this->getAccessor()->getValue($result, self::PATH_REQUIRE);
        if ($packages) {
            $this->getAccessor()->setValue(
                $result,
                self::PATH_REQUIRE,
                $this->sortPackages($packages)
            );
        }

        $packages = $this->getAccessor()->getValue($result, self::PATH_REQUIRE_DEV);
        if ($packages) {
            $this->getAccessor()->setValue(
                $result,
                self::PATH_REQUIRE_DEV,
                $this->sortPackages($packages)
            );
        }

        return $result;
    }

    /**
     * Sorts packages by importance (platform packages first, then PHP dependencies) and alphabetically.
     *
     * @see https://getcomposer.org/doc/02-libraries.md#platform-packages
     * @see https://github.com/composer/composer/blob/master/src/Composer/Json/JsonManipulator.php#L113
     *
     * @copyright Nils Adermann <naderman@naderman.de>
     * @copyright Jordi Boggiano <j.boggiano@seld.be>
     *
     * @param array $packages
     *
     * @return array
     */
    private function sortPackages(array $packages = []): array
    {
        $prefix = function ($requirement) {
            if (preg_match(PlatformRepository::PLATFORM_PACKAGE_REGEX, $requirement)) {
                return preg_replace(
                    [
                        '/^php/',
                        '/^hhvm/',
                        '/^ext/',
                        '/^lib/',
                        '/^\D/',
                    ],
                    [
                        '0-$0',
                        '1-$0',
                        '2-$0',
                        '3-$0',
                        '4-$0',
                    ],
                    $requirement
                );
            }

            return '5-'.$requirement;
        };

        uksort(
            $packages,
            function ($left, $right) use ($prefix) {
                return strnatcmp($prefix($left), $prefix($right));
            }
        );

        return $packages;
    }

    /**
     * Get ignore nodes.
     *
     * IMPORTANT: Nodes must be defined in PropertyAccessor format.
     *
     * @return array
     */
    protected function getIgnoreNodes(): array
    {
        return $this->getAccessor()->getValue($this->getConfig(), self::PATH_SYNC_IGNORE_NODES) ?: [];
    }
}
