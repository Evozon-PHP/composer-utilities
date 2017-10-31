<?php

namespace EvozonPhp\ComposerUtilities\Command;

use Composer\Command\BaseCommand;
use Composer\Json\JsonFile;
use EvozonPhp\ComposerUtilities\Handler\SyncJsonHandler;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Synchronize source to destination composer json files.
 *
 * Useful for monolith development where you need to sync `dev.json` to `composer.json`
 *
 * @copyright Evozon Systems SRL (http://www.evozon.com/)
 * @author    Constantin Bejenaru <constantin.bejenaru@evozon.com>
 */
class SyncJsonCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sync:json');
        $this->setDescription(
            'Synchronize your source dev.json to the destination composer.json'
        );
        $this->setDefinition(
            [
                new InputOption(
                    'source',
                    's',
                    InputOption::VALUE_OPTIONAL,
                    'Path to source composer.json file.',
                    SyncJsonHandler::DEFAULT_SOURCE_COMPOSER_FILE
                ),
                new InputOption(
                    'target',
                    't',
                    InputOption::VALUE_OPTIONAL,
                    'Path to target (destination) composer.json file.',
                    SyncJsonHandler::DEFAULT_TARGET_COMPOSER_FILE
                ),
                new InputOption(
                    'dry-run',
                    null,
                    InputOption::VALUE_NONE,
                    'Outputs the operations but will not execute anything.'
                ),
            ]
        )
            ->setHelp(
                <<<EOT
The <info>sync:json</info> command reads a source json file and synchronizes it to the destination file.
Useful in monolith development where you have to maintain <info>dev.json</info> and <info>composer.json</info>.

<info>php composer.phar sync:json --source dev.json --target composer.json</info>

You may define nodes that you want to ignore during sync.
Please note, nodes are defined in `PropertyAccess` (https://goo.gl/VUiBsU) format.
<info>
{    
    "config": {
        "composer-utilities": {
            "sync": {
                "ignore": {
                    "nodes": [
                        "[require][vendorAbc/libraryAbc]",
                        "[require][vendorXyz/bundleXyz]",
                        "[repositories]"
                    ]
                }
            }
        }
    }
}
</info>

EOT
            );
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->getComposer();
        $io = $this->getIO();

        $source = new JsonFile($input->getOption('source'));
        $target = new JsonFile($input->getOption('target'));

        $handler = new SyncJsonHandler($composer, $io);
        $result = $handler->handle($source, $target);

        $resultString = JsonFile::encode($result);

        if ($input->getOption('dry-run')) {
            $io->write($resultString);

            return;
        }

        try {
            $target->write($result);

            $io->write(
                sprintf(
                    'Successfully synchronized from source <info>%s</info> to target <info>%s</info>.',
                    $input->getOption('source'),
                    $input->getOption('target')
                )
            );
        } catch (Exception $e) {
            $io->write(
                sprintf('An error occurred while trying to write <info>%s</info>.', $input->getOption('target'))
            );
            $io->write('Make sure the target file has correct writing permissions.');
            $io->write('Alternatively try the <info>--dry-run</info> option to dump the full content.');

            $io->write($e->getMessage());
        }
    }
}
