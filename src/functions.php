<?php

declare(strict_types=1);

use Chiron\Routing\MatchingResult;

if (! function_exists('route_parameter')) {
    /**
     * Get a given parameter from the route.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @throws ScopeException If the matchingresult is not presents in the container scope.
     *
     * @return mixed
     */
    function route_parameter(string $name, $default = null)
    {
        // TODO : soit créer une méthode request() dans un fichier functions.php stocké dans le package chiron/http qui se charge de retourner l'objet ServerRequestInterface, soit ajouter un alias 'request' pour accéder plus facilement à cette information !!!!
        $matchingResult = container(MatchingResult::class);

        // TODO : créer une classe Arr dans chiron/core/support avec une méthode get() qui fait ce type de code de maniére plus propre !!!!
        // TODO : ou alors créer directement une méthode 'getParameter(string $name, $default = null)' dans la classe MatchingResult qui ferait le bout de code ci dessous !!!
        return $matchingResult->getMatchedParameters()[$name] ?? $default;
    }
}
