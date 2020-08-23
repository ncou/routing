<?php

namespace Chiron\Routing\Bootloader;

use Chiron\Bootload\AbstractBootloader;
use Chiron\Http\Config\HttpConfig;
use Chiron\Routing\RouteCollector;

// TODO : il faudrait plutot créer un package "chiron/http" qui aurait aussi en dépendance le package "chiron/router" et on déplacerait ce bootloader dans le package http (idem pour la classe HttpConfig qui serait dans le package http !!!!)

// TODO : transformer cette classe en un ServiceProvider et dans le binding du RouteCollector on en profiterai pour aussi initialiser le basePath ???? non ????
class RouteCollectorBootloader extends AbstractBootloader
{
    public function boot(RouteCollector $routeCollector, HttpConfig $httpConfig)
    {
        $routeCollector->setBasePath($httpConfig->getBasePath());
    }
}
