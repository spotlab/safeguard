<?php

namespace Spotlab\Safeguard\Command;

use Spotlab\Safeguard\Guardian;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class restore
 * @package Spotlab\Safeguard\Command
 */
class Restore extends Command
{
    /**
     * Set us up the command!
     */
    public function configure()
    {
        $this->setName('restore')
             ->setDescription('Restore SQL from config file');

        $this
            ->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'The name of the project to restore')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'The absolute path to the backup file');

        $this->addArgument('config', InputArgument::REQUIRED, 'The path to the config file (.json)');
    }

    /**
     * Parses the clover XML file and spits out coverage results to the console.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Get options
        $project = $input->getOption('project');
        if ($input->getOption('file')) {
            $file = $input->getOption('file');
        } else {
            $file = '';
        }

        // First step : Analysing config
        $config_path = $input->getArgument('config');
        $guardian = new Guardian($config_path);
        $output->writeln(sprintf('Analysing config file : <info>%s</info>', $config_path));
        $output->write("\n");

        $output->writeln(sprintf('> Start SQL restore : <info>%s</info>', $project));
        $output->writeln('------------------------------');

        try {
            $guardian->restoreDatabase($project, $file);
        } catch (\Exception $e) {
            if($e->getCode() == 0) $style = 'error';
            else $style = 'comment';
            $output->write(sprintf(' : <' . $style . '>%s</' . $style . '>', $e->getMessage()));
        }
        $output->write("\n");

        $output->writeln('Finished : <info>Done</info>');
    }
}
