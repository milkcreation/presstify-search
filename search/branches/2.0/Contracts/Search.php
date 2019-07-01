<?php declare(strict_types=1);

namespace tiFy\Plugins\Search\Contracts;

use Psr\Container\ContainerInterface as Container;

interface Search
{
    /**
     * Initialisation
     *
     * @return static
     */
    public function initialize(): Search;

    /**
     * Récupération du conteneur d'injection de dépendances.
     *
     * @return Container|null
     */
    public function getContainer(): ?Container;
}
