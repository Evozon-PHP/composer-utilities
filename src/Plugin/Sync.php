<?php

namespace EvozonPhp\ComposerUtilities\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Plugin\Capable as CapableInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use EvozonPhp\ComposerUtilities\Handler\SyncJsonHandler;
use InvalidArgumentException;

/**
 * Composer synchronization plugin.
 *
 * @copyright Evozon Systems SRL (http://www.evozon.com/)
 * @author    Constantin Bejenaru <constantin.bejenaru@evozon.com>
 */
class Sync implements PluginInterface, CapableInterface, EventSubscriberInterface
{
    /**
     * Default composer file.
     *
     * @var string
     */
    const DEFAULT_COMPOSER_FILE = 'composer.json';

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities(): array
    {
        return [
            'Composer\Plugin\Capability\CommandProvider' => 'EvozonPhp\ComposerUtilities\Plugin\Capability\SyncCapability',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => [
                ['synchronize', -9999],
            ],
            ScriptEvents::POST_UPDATE_CMD => [
                ['synchronize', -9999],
            ],
        ];
    }

    /**
     * Synchronize.
     *
     * @param Event $event
     */
    public function synchronize(Event $event)
    {
        $composer = $event->getComposer();
        $io = $event->getIO();

        if (!$event->isDevMode()) {
            $io->write(
                sprintf(
                    'You are not running in <info>dev</info> mode. Skipping <info>%s</info> sync.',
                    self::DEFAULT_COMPOSER_FILE
                )
            );
        }

        $composerFileSource = $composer->getConfig()->getConfigSource()->getName();

        if (self::DEFAULT_COMPOSER_FILE === basename($composerFileSource)) {
            $io->write(
                sprintf(
                    'You are using the default <info>%s</info> file. Synchronize manually in case you need it.',
                    self::DEFAULT_COMPOSER_FILE
                )
            );
            $io->write('See <info>composer sync:json -h</info> for more.');
        }

        // Ask if we continue
        $continue = $io->askConfirmation(
            sprintf('Do you want to synchronize your <info>%s</info> file? [y/n] ', self::DEFAULT_COMPOSER_FILE),
            true
        );

        if (!$continue) {
            return;
        }

        /**
         * Validate composer file.
         *
         * @param string $composer
         *
         * @return string
         */
        $validateComposer = function (string $composer): string {
            $file = new JsonFile($composer);

            if (!$file->exists()) {
                throw new InvalidArgumentException(
                    sprintf('File `%s` does not exist!', $composer)
                );
            }

            $file->validateSchema(JsonFile::STRICT_SCHEMA);

            return $composer;
        };

        // Make sure we get the correct source file
        $composerFileSource = $io->askAndValidate(
            sprintf('What is the source composer file? [%s]', $composerFileSource),
            $validateComposer,
            3,
            $composerFileSource
        );

        $composerFileTarget = dirname($composerFileSource).'/'.self::DEFAULT_COMPOSER_FILE;

        // Make sure we get the correct target file
        $composerFileTarget = $io->askAndValidate(
            sprintf('What is the target composer file? [%s]', $composerFileTarget),
            $validateComposer,
            3,
            $composerFileTarget
        );

        $source = new JsonFile($composerFileSource);
        $target = new JsonFile($composerFileTarget);

        $sync = new SyncJsonHandler($composer, $io);
        $result = $sync->handle($source, $target);

        try {
            $target->write($result);

            $io->write(
                sprintf(
                    'Successfully synchronized from source <info>%s</info> to target <info>%s</info>.',
                    $composerFileSource,
                    $composerFileTarget
                )
            );
        } catch (Exception $e) {
            $io->write(sprintf('An error occurred while trying to write <info>%s</info>.', $composerFileTarget));
            $io->write($e->getMessage());
        }
    }
}
