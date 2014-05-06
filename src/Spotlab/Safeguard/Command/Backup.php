<?php

namespace Spotlab\Safeguard\Command;

use Spotlab\Safeguard\Guardian;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Backup
 * @package Spotlab\Safeguard\Command
 */
class Backup extends Command
{
    /**
     * Set us up the command!
     */
    public function configure()
    {
        $this->setName('backup')
             ->setDescription('Backup SQL and File from config file');

        $this->addArgument(
            'config',
            InputArgument::REQUIRED,
            'The path to the config file (.json)'
        );
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
        // First step : Analysing config
        $config_path = $input->getArgument('config');
        $guardian = new Guardian($config_path);
        $output->writeln(sprintf('Analysing config file : <info>%s</info>', $config_path));

        // Actions for every projects in config
        $projects = $guardian->getProjects();
        
        foreach ($projects as $project) {
            
            $output->writeln(sprintf('> Start project : <info>%s</info>', $project));

            if (!empty($project['database'])) {
                $output->write('>> Dumping database');
                try {
                    $backupDatabase = $guardian->backupDatabase($project);
                    if (!empty($backupDatabase)) {
                        $output->writeln(' : <info>' . $backupDatabase['name'] . '" (' . $backupDatabase['size'] . ')</info>');
                    }
                } catch (\Exception $e) {
                    if($e->getCode() == 0) $style = 'error';
                    else $style = 'comment';
                    $output->writeln(sprintf(' : <' . $style . '>>> %s</' . $style . '>', $e->getMessage()));
                }
            }

            if (!empty($project['archive'])) {
                $output->write('>> Creating archive');
                try {
                    $backupArchive = $guardian->backupArchive($project);
                    if (!empty($backupArchive)) {
                        $output->writeln(' : <info>' . $backupArchive['name'] . '" (' . $backupArchive['size'] . ')</info>');
                    }
                } catch (\Exception $e) {
                    if($e->getCode() == 0) $style = 'error';
                    else $style = 'comment';
                    $output->writeln(sprintf(' : <' . $style . '>>> %s</' . $style . '>', $e->getMessage()));
                }
            }
        }

        $output->writeln('Finished : <info>Done</info>');
    }
}
