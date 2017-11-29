<?php

namespace EvozonPhp\ComposerUtilities\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable as CapableInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use EvozonPhp\ComposerUtilities\Handler\ParametersUpdateHandler;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Yaml;

/**
 * Composer parameter update plugin.
 *
 * @copyright Evozon Systems SRL (http://www.evozon.com/)
 * @author    Constantin Bejenaru <constantin.bejenaru@evozon.com>
 */
class ParametersUpdate implements PluginInterface, CapableInterface, EventSubscriberInterface
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
            'Composer\Plugin\Capability\CommandProvider' => 'EvozonPhp\ComposerUtilities\Plugin\Capability\ParametersUpdateCapability',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => [
                ['update', -9999],
            ],
            ScriptEvents::POST_UPDATE_CMD => [
                ['update', -9999],
            ],
        ];
    }

    /**
     * Update paramters.
     *
     * @param Event $event
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function update(Event $event)
    {
        $composer = $event->getComposer();
        $io = $event->getIO();

        $config = $this->composer->getConfig()->get('composer-utilities');
        $accessor = PropertyAccess::createPropertyAccessor();

        $source = $accessor->isReadable($config, ParametersUpdateHandler::PATH_FILE_DIST) ?
            $accessor->getValue($config, ParametersUpdateHandler::PATH_FILE_DIST) :
            ParametersUpdateHandler::DEFAULT_SOURCE_FILE;

        $target = $accessor->isReadable($config, ParametersUpdateHandler::PATH_FILE) ?
            $accessor->getValue($config, ParametersUpdateHandler::PATH_FILE) :
            ParametersUpdateHandler::DEFAULT_TARGET_FILE;

        // Ask if we continue
        $continue = $io->askConfirmation(
            sprintf(
                'Do you want to update configured paramters from <info>%s</info> to <info>%s</info> file? [y/n] ',
                $source,
                $target
            ),
            true
        );

        if (!$continue) {
            return;
        }

        $handler = new ParametersUpdateHandler($composer, $io);
        $result = $handler->handle($source, $target);

        $resultString = Yaml::dump($result, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        if (!is_dir($dir = dirname($target))) {
            mkdir($dir, 0755, true);
        }

        $result = file_put_contents($target, $resultString);

        if ($result) {
            $io->write(
                sprintf(
                    'Successfully updated parameters from source <info>%s</info> to target <info>%s</info>.',
                    $source,
                    $target
                )
            );
        } else {
            $io->write(
                sprintf('An error occurred while trying to write file <info>%s</info>.', $target)
            );
            $io->write('Make sure the target file has correct writing permissions.');
            $io->write('Alternatively try the <info>--dry-run</info> option to dump the full content.');
        }
    }
}
