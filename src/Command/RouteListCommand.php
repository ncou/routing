<?php

declare(strict_types=1);

namespace Chiron\Routing\Command;

use Chiron\Core\Command\AbstractCommand;
use Chiron\Http\CallableHandler;
use Chiron\Http\Message\RequestMethod as Method;
use Chiron\Routing\Route;
use Chiron\Routing\Map;
use Chiron\Routing\Target\Action;
use Chiron\Routing\Target\Controller;
use Chiron\Routing\Target\Group;
use Chiron\Routing\Target\Namespaced;
use ReflectionException;
use ReflectionObject;

//https://github.com/spiral/framework/blob/d17c175e85165456fbd2d841c8e81165e371675c/src/Framework/Command/Router/ListCommand.php
//https://github.com/spiral/framework/blob/d17c175e85165456fbd2d841c8e81165e371675c/tests/Framework/Framework/RouteListTest.php

//https://github.com/top-think/framework/blob/6.0/src/think/console/command/RouteList.php

final class RouteListCommand extends AbstractCommand
{
    protected static $defaultName = 'route:list';

    protected function configure()
    {
        $this->setDescription('List application routes.');
    }

    public function perform(Map $map): int
    {
        //die(var_dump($this->input->hasParameterOption(['-n'], true)));
        //die(var_dump($this->input->hasParameterOption(['--no-interaction'], true)));

        //$this->call('hello:world');

        // TODO : corriger ce cas là car on se retrouve avec un séparateur qui n'est plus jaune car l'instruction de reset "\e[0m" coupe le style initialement appliqué.
        //$this->alert5("\033[2;35m". "\033[41m". 'TEST_couleur' . "\e[0m");

        //$this->alert5("\033[2;35m". "\033[41m". 'TEST_couleur' . "\033[0m");
        //$this->alert5("\033[2;35m". "\033[41m". 'TEST_couleur' . "\033[0m");
        //$this->alert4("<bg=red>". 'TEST_couleur' . "</>");

        /*
                $this->line('TEST_1', 'emergency');
                $this->line('TEST_2', 'alert');
                $this->line('TEST_3', 'critical');


                $this->line('TEST_4', 'error');
                $this->line('TEST_5', 'caution');
                $this->line('TEST_6', 'warning');
                $this->line('TEST_7', 'info');
                $this->line('TEST_7_Debug', 'fg=cyan');

                $this->line('TEST_8', 'success');
                $this->line('TEST_9', 'comment');
                $this->line('TEST_10', 'question');

                $this->line('TEST_11', 'notice');

                $this->line('TEST_12', 'default');


                $this->line('<info>TEST</info>_<comment>comment</comment>');

                $this->line('TEST_99', 'foobar');

                $this->line("\033[2;35m". "\033[41m". 'TEST_couleur' . "\e[0m");
                $this->line("\033[41m" . "\033[2;35m". 'TEST_couleur' . "\e[0m");

                $this->line('TEST_couleur', "\033[2;35m");
        */

        $grid = $this->table(['Method:', 'Path:', 'Handler:']);

        foreach ($map as $route) {
            $grid->addRow(
                [
                    $this->getAllowedMethods($route),
                    $this->getPath($route),
                    $this->getHandler($route),
                ]
            );
        }

        $grid->render();

        return self::SUCCESS;
    }

    /**
     * @param Route $route
     *
     * @return string
     */
    private function getAllowedMethods(Route $route): string
    {
        if ($route->getAllowedMethods() === Method::ANY) {
            return '*';
        }

        $result = [];

        // TODO : utiliser la classe "Method" pour utiliser les constantes des verbs http (GET/POST/PUT...etc). + ajouter les autres verbes du genre PATCH/TRACE/OPTION...etc
        foreach ($route->getAllowedMethods() as $verb) {
            switch (strtolower($verb)) {
                case 'get':
                    $verb = '<fg=green>GET</>';

                    break;
                case 'post':
                    $verb = '<fg=blue>POST</>';

                    break;
                case 'put':
                    $verb = '<fg=yellow>PUT</>';

                    break;
                case 'delete':
                    $verb = '<fg=red>DELETE</>';

                    break;
            }

            $result[] = $verb;
        }

        return implode(', ', $result);
    }

    /**
     * @param Route $route
     *
     * @return string
     */
    private function getPath(Route $route): string
    {
        $pattern = $this->getValue($route, 'path');

        // TODO : vérifier l'utilité de ce bout de code !!!!
        /*
        $pattern = str_replace(
            '[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}',
            'uuid',
            $pattern
        );*/

        // TODO : attention dans le cas du router Aura.Router le séparateur des expression n'est pas '{xxx}' mais '<xxx>' on aura un probléme !!!!
        return preg_replace_callback(
            '/{([^}]*)}/',
            static function ($m) {
                return sprintf('<fg=magenta>%s</>', $m[0]);
            },
            $pattern
        );
    }

    /**
     * @param Route $route
     *
     * @throws ReflectionException
     *
     * @return string
     */
    private function getHandler(Route $route): string
    {
        $handler = $this->getValue($route, 'handler');

        switch (true) {
            // TODO : virer ce case qui ne sert à rien et ajouter un case pour la classe "Callback"
            case $handler instanceof CallableHandler:
                // TODO : à coder !!!!!
                return 'Callback()';
            /*
                $reflection = new \ReflectionFunction($handler);
                return sprintf(
                    'Closure(%s:%s)',
                    basename($reflection->getFileName()),
                    $reflection->getStartLine()
                );
            */
            case $handler instanceof Action:
                return sprintf(
                    '%s->%s',
                    $this->getValue($handler, 'controller'),
                    implode('|', (array) $this->getValue($handler, 'action'))
                );
            case $handler instanceof Controller:
                return sprintf(
                    '%s->*',
                    $this->getValue($handler, 'controller')
                );
            case $handler instanceof Group:
                $result = [];
                foreach ($this->getValue($handler, 'controllers') as $alias => $class) {
                    $result[] = sprintf('%s => %s', $alias, $class);
                }

                return implode("\n", $result);
            case $handler instanceof Namespaced:
                return sprintf(
                    '%s\*%s->*',
                    $this->getValue($handler, 'namespace'),
                    $this->getValue($handler, 'postfix')
                );
        }

        return '';
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    //https://github.com/nette/web-addons.nette.org/blob/e985a240f30d2d4314f97cb2fa9699476d0c0a68/tests/libs/Access/Property.php
    private function getValue(object $object, string $property)
    {
        $r = new ReflectionObject($object);
        $prop = $r->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($object);
    }
}
