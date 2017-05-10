<?php

namespace EvozonPhp\ComposerUtilities\Sync;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Repository\PlatformRepository;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Synchronize source to target json files.
 *
 * @copyright Evozon Systems SRL (http://www.evozon.com/)
 * @author    Constantin Bejenaru <constantin.bejenaru@evozon.com>
 */
class Json
{
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
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var PropertyAccessor
     */
    private $accessor;

    /**
     * Constructor.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->setComposer($composer);
        $this->setIo($io);
    }

    /**
     * Synchronize source to target.
     *
     * @param JsonFile $source
     * @param JsonFile $target
     *
     * @return array
     */
    public function synchronize(JsonFile $source, JsonFile $target): array
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

    /**
     * Get utilities configuration.
     *
     * @return array
     */
    protected function getConfig(): array
    {
        return (array) $this->getComposer()->getConfig()->get('composer-utilities');
    }

    /**
     * Get Accessor.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @return PropertyAccessor
     */
    protected function getAccessor(): PropertyAccessor
    {
        if (null === $this->accessor) {
            $this->accessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->accessor;
    }

    /**
     * Get Composer.
     *
     * @return Composer
     */
    public function getComposer(): Composer
    {
        return $this->composer;
    }

    /**
     * Set Composer.
     *
     * @param Composer $composer
     *
     * @return Json
     */
    protected function setComposer(Composer $composer): Json
    {
        $this->composer = $composer;

        return $this;
    }

    /**
     * Get Io.
     *
     * @return IOInterface
     */
    public function getIo(): IOInterface
    {
        return $this->io;
    }

    /**
     * Set Io.
     *
     * @param IOInterface $io
     *
     * @return Json
     */
    protected function setIo(IOInterface $io): Json
    {
        $this->io = $io;

        return $this;
    }
}
