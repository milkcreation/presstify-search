<?php declare(strict_types=1);

namespace tiFy\Plugins\Search\Contracts;

use Psr\Container\ContainerInterface as Container;

interface Search
{
    /**
     * Initialisation.
     *
     * @return static
     */
    public function build(): Search;

    /**
     * Récupération d'une instance de pilote de recherche.
     *
     * @param string $name
     *
     * @return Searcher|null
     */
    public function get(string $name): ?Searcher;

    /**
     * Récupération du conteneur d'injection de dépendances.
     *
     * @return Container|null
     */
    public function getContainer(): ?Container;

    /**
     * Déclaration d'un pilote de recherche.
     *
     * @param string $name
     * @param Searcher|array|null $attrs
     *
     * @return Searcher
     */
    public function register(string $name, $attrs = null): Searcher;
}
