<?php

declare(strict_types=1);

namespace Chiron\Routing;

//https://github.com/yiisoft/http/blob/master/src/Method.php
// TODO : classe à renommer en HttpMethods ????
// TODO : enrichir la méthode ->any()    https://github.com/narrowspark/framework/blob/2866c328dfeec4cc78f8c25f412832bb2e9da5e2/src/Viserio/Component/Routing/Router.php#L191
// TODO : classe à déplacer dans un projet http-method et ajouter cette dépendance composer dans le projet router.
final class Method
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
    public const PATCH = 'PATCH';
    public const HEAD = 'HEAD';
    public const OPTIONS = 'OPTIONS';
    public const CONNECT = 'CONNECT';
    public const TRACE = 'TRACE';

    public const ANY = [
        self::GET,
        self::POST,
        self::PUT,
        self::DELETE,
        self::PATCH,
        self::HEAD,
        self::OPTIONS,
        self::CONNECT,
        self::TRACE
    ];

    /**
     * Validate the provided HTTP method names.
     *
     * Validates, and then normalizes to upper case.
     *
     * @param string[] An array of HTTP method names.
     *
     * @throws Exception InvalidArgumentException for any invalid method names.
     *
     * @return string[]
     */
    // TODO : regarder aussi ici pour une méthode de vérification : https://github.com/cakephp/cakephp/blob/master/src/Routing/Route/Route.php#L169
    // TODO : fonction à renommer en "validate()" ????
    // TODO : il faudrait plutot que cette fonction soit renommée en isValid($string): bool et qu'elle retourne un booléen true/false sur la validée de la string, et les exceptions seraient levée plutot au niveau de cla sse Route selon la valeur de retour du booléen.
    //https://github.com/slimphp/Slim-Psr7/blob/master/src/Request.php#L155
    public static function validateHttpMethods(array $methods): array
    {
        if (false === array_reduce($methods, function ($valid, $method) {
            if ($valid === false) {
                return false;
            }
            if (! is_string($method)) {
                return false;
            }
            //if (! preg_match('/^[!#$%&\'*+.^_`\|~0-9a-z-]+$/i', $method)) {
            if (preg_match("/^[!#$%&'*+.^_`|~0-9a-z-]+$/i", $method) !== 1) {
            //if (! preg_match("/^[!#$%&'*+.^_`|~0-9a-z-]+$/i", $method)) {
                return false;
            }

            return $valid;
        }, true)) {
            throw new \InvalidArgumentException('One or more HTTP methods were invalid');
        }

        // TODO : reporter ce strtoupper dans la classe Route. cela n'a rien à faire dans une méthode de validation
        // TODO : il faudrait même éviter d'utiliser un strtoupper !!!!
        return array_map('strtoupper', $methods);
    }

    /**
     * Standardize custom http method name
     * For the methods that are not defined in this enum
     *
     * @param string $method
     * @return string
     */
    // TODO : réfléchir si on conserve cette méthode !!!!
    public static function custom(string $method): string
    {
        return strtoupper($method);
    }
}
