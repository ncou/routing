<?php

declare(strict_types=1);

namespace Chiron\Routing\Command;

use Chiron\Core\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

//https://github.com/selfinvoking/laravel-rr/blob/master/app/Console/Commands/RoadRunnerCommand.php
// TODO : passer la classe en final.
// TODO : déplacer dans le package chiron/sapi !!!!

//https://github.com/symfony/web-server-bundle/blob/4.4/WebServer.php

class ServeCommand extends AbstractCommand
{
    public const EXIT_CODE_NO_DOCUMENT_ROOT = 2;
    public const EXIT_CODE_NO_ROUTING_FILE = 3;
    public const EXIT_CODE_ADDRESS_TAKEN_BY_ANOTHER_PROCESS = 5;

    private const DEFAULT_PORT = 8080;
    private const DEFAULT_DOCROOT = 'public';

    protected static $defaultName = 'serve';

    public function configure(): void
    {
        $this
            ->setDescription('Runs PHP built-in web server')
            ->setHelp('In order to access server from remote machines use 0.0.0.0:8000. That is especially useful when running server in a virtual machine.')
            ->addArgument('address', InputArgument::OPTIONAL, 'Host to serve at', 'localhost')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Port to serve at', self::DEFAULT_PORT)
            ->addOption('docroot', 't', InputOption::VALUE_OPTIONAL, 'Document root to serve from', self::DEFAULT_DOCROOT)
            ->addOption('router', 'r', InputOption::VALUE_OPTIONAL, 'Path to router script');
    }

    public function perform()
    {
        // TODO : améliorer le code !!!!
        $input = $this->input;
        $output = $this->output;

        $io = new SymfonyStyle($input, $output);
        $address = $input->getArgument('address');

        $port = $input->getOption('port');
        $docroot = $input->getOption('docroot');
        $router = $input->getOption('router');

        $documentRoot = getcwd() . '/' . $docroot; // TODO: can we do it better?

        if (strpos($address, ':') === false) {
            $address .= ':' . $port;
        }

        if (! is_dir($documentRoot)) {
            $io->error("Document root \"$documentRoot\" does not exist.");

            return self::EXIT_CODE_NO_DOCUMENT_ROOT;
        }

        if ($this->isAddressTaken($address)) {
            $io->error("http://$address is taken by another process.");
            //sprintf('A process is already listening on http://%s.', $config->getAddress()));

            return self::EXIT_CODE_ADDRESS_TAKEN_BY_ANOTHER_PROCESS;
        }

        // TODO : à virer c'est un test
        //$router = 'D:\xampp\htdocs\nano5\rewrite2.php';

        if ($router !== null && ! file_exists($router)) {
            $io->error("Routing file \"$router\" does not exist.");

            return self::EXIT_CODE_NO_ROUTING_FILE;
        }

        $output->writeLn("Server started on http://{$address}/");
        $output->writeLn("Document root is \"{$documentRoot}\"");
        if ($router) {
            $output->writeLn("Routing file is \"$router\"");
        }
        $output->writeLn('Quit the server with CTRL-C or COMMAND-C.');

        passthru('"' . PHP_BINARY . '"' . " -S {$address} -t \"{$documentRoot}\" $router");

        //https://github.com/selfinvoking/laravel-rr/blob/master/app/Console/Commands/RoadRunnerCommand.php
        /*
        // TODO : utiliser ce bout de code pour vérifier que l'executable "rr" ou "rr.exe" est installé à la racine du projet. si c'est le cas on pourra executer le serveur RoadRunner !!!!
                if (@is_executable($php = PHP_BINDIR.('\\' === \DIRECTORY_SEPARATOR ? '\\php.exe' : '/php'))) {
                    return $php;
                }
        */
        //passthru('"D:\xampp\htdocs\nano5\rr.exe" serve -v -d');
        //passthru('".\rr.exe" serve -v -d');
    }

    /**
     * @param string $address server address
     *
     * @return bool if address is already in use
     */
    private function isAddressTaken(string $address): bool
    {
        [$hostname, $port] = explode(':', $address);

        $fp = @fsockopen($hostname, (int)$port, $errno, $errstr, 1);
        if ($fp === false) {
            return false;
        }
        fclose($fp);

        return true;
    }
}
