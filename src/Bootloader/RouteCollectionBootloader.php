<?php

declare(strict_types=1);

namespace Chiron\Routing\Bootloader;

use Chiron\Core\Container\Bootloader\AbstractBootloader;
use Chiron\Http\Config\HttpConfig;
use Chiron\Routing\RouteCollection;

// TODO : il faudrait plutot créer un package "chiron/http" qui aurait aussi en dépendance le package "chiron/router" et on déplacerait ce bootloader dans le package http (idem pour la classe HttpConfig qui serait dans le package http !!!!)

// TODO : transformer cette classe en un ServiceProvider et dans le binding du RouteCollection on en profiterai pour aussi initialiser le basePath ???? non ????
class RouteCollectionBootloader extends AbstractBootloader
{
    public function boot(RouteCollection $routes, HttpConfig $httpConfig)
    {
        $routes->setBasePath($httpConfig->getBasePath());
    }
}
