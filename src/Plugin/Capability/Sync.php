<?php

namespace EvozonPhp\ComposerUtilities\Plugin\Capability;

use Composer\Plugin\Capability\CommandProvider as CommandProviderInterface;
use EvozonPhp\ComposerUtilities\Command\SyncJson;

/**
 * Add custom sync commands to composer.
 *
 * @copyright Evozon Systems SRL (http://www.evozon.com/)
 * @author    Constantin Bejenaru <constantin.bejenaru@evozon.com>
 */
class Sync implements CommandProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCommands(): array
    {
        return [new SyncJson()];
    }
}
