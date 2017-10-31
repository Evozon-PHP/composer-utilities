<?php

namespace EvozonPhp\ComposerUtilities\Command;

use Composer\Command\BaseCommand;
use EvozonPhp\ComposerUtilities\Handler\ParametersUpdateHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * (Force) Update parameters from parameters.yml.dist to parameters.yml.
 *
 * @copyright Evozon Systems SRL (http://www.evozon.com/)
 * @author    Constantin Bejenaru <constantin.bejenaru@evozon.com>
 */
class ParametersUpdateCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function configure()
    {
        $this->setName('parameters:update');
        $this->setDescription(
            'Update parameters from parameters.yml.dist to parameters.yml'
        );
        $this->setDefinition(
            [
                new InputOption(
                    'source',
                    's',
                    InputOption::VALUE_OPTIONAL,
                    'Path to source parameters.yml.dist file.',
                    ParametersUpdateHandler::DEFAULT_SOURCE_FILE
                ),
                new InputOption(
                    'target',
                    't',
                    InputOption::VALUE_OPTIONAL,
                    'Path to target parameters.yml file.',
                    ParametersUpdateHandler::DEFAULT_TARGET_FILE
                ),
                new InputOption(
                    'dry-run',
                    null,
                    InputOption::VALUE_NONE,
                    'Outputs the result but will not execute anything.'
                ),
            ]
        )
            ->setHelp(
                <<<EOT
The <info>parameters:update</info> command reads a source parameters file and forces update on specified parameters to the destination file.
Useful when parameters change in <info>parameters.yml.dist</info> but updating them <info>parameters.yml</info> is tedious.

<info>php composer.phar parameters:update --source app/config/parameters.yml.dist --target app/config/parameters.yml</info>

You must define nodes that you want to update.
<info>
{    
    "extra": {
        "parameters-update": {
            "file": "app/config/parameters.yml",
            "dist-file": "app/config/parameters.yml.dist",
            "parameters": [
                "cross_app_urls",
                "database_driver"
            ]
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

        $source = $input->getOption('source');
        $target = $input->getOption('target');

        try {
            $handler = new ParametersUpdateHandler($composer, $io);
            $result = $handler->handle($source, $target);

            $resultString = Yaml::dump($result, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

            if ($input->getOption('dry-run')) {
                $io->write($resultString);

                return;
            }

            $filesystem = new Filesystem();
            $filesystem->dumpFile($target, $resultString);

            $io->write(
                sprintf(
                    'Successfully updated parameters from source <info>%s</info> to target <info>%s</info>.',
                    $source,
                    $target
                )
            );
        } catch (IOException $exception) {
            $io->write(
                sprintf('An error occurred while trying to write <info>%s</info>.', $exception->getPath())
            );
            $io->write('Make sure the target file has correct writing permissions.');
            $io->write('Alternatively try the <info>--dry-run</info> option to dump the full content.');

            $io->write($exception->getMessage());
        } catch (Exception $exception) {
            $io->write(sprintf('An error occurred.'));
            $io->write($exception->getMessage());
        }
    }
}
