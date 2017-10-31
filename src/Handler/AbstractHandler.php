<?php

namespace EvozonPhp\ComposerUtilities\Handler;

use Composer\Composer;
use Composer\IO\IOInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Abstract Handler.
 *
 * @copyright Evozon Systems SRL (http://www.evozon.com/)
 * @author    Constantin Bejenaru <constantin.bejenaru@evozon.com>
 */
abstract class AbstractHandler
{
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
    protected $accessor;

    /**
     * Constructor.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Get utilities configuration.
     *
     * @return array
     */
    protected function getConfig(): array
    {
        return (array) $this->composer->getConfig()->get('composer-utilities');
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
}
