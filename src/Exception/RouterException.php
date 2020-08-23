<?php

declare(strict_types=1);

namespace Chiron\Routing\Exception;

use RuntimeException;

// TODO : créer plutot une interface et laisser chaque implémentation de Router créer ses exception ? comme pour le ContainerExceptionInterface ????
class RouterException extends RuntimeException
{
}
