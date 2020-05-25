<?php declare(strict_types=1);

namespace tiFy\Plugins\Search;

use Psr\Container\ContainerInterface as Container;
use tiFy\Plugins\Search\Contracts\{Search as SearchContract, Searcher as SearcherContract};

/**
 * @desc Extension PresstiFy de recherche avancée.
 * @author Jordy Manner <jordy@milkcreation.fr>
 * @package tiFy\Plugins\Search
 * @version 2.0.2
 *
 * USAGE :
 * Activation
 * ---------------------------------------------------------------------------------------------------------------------
 * Dans config/app.php ajouter \tiFy\Plugins\Search\SearchServiceProvider à la liste des fournisseurs de services.
 * ex.
 * <?php
 * ...
 * use tiFy\Plugins\Search\SearchServiceProvider;
 * ...
 *
 * return [
 *      ...
 *      'providers' => [
 *          ...
 *          Search\SearchServiceProvider::class
 *          ...
 *      ]
 * ];
 *
 * Configuration
 * ---------------------------------------------------------------------------------------------------------------------
 * Dans le dossier de config, créer le fichier search.php
 * @see Resources/config/search.php
 */
class Search implements SearchContract
{
    /**
     * Indicateur d'initialisation.
     * @var bool
     */
    protected $built = false;

    /**
     * Instance du conteneur d'injection de dépendances.
     * @var Container|null
     */
    protected $container;

    /**
     * Liste des instances des pilotes de recherche.
     * @var SearcherContract[]|array
     */
    protected $searcher = [];

    /**
     * CONSTRUCTEUR.
     *
     * @param Container|null $container
     *
     * @return void
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function build(): SearchContract
    {
        if (!$this->built) {
            foreach (config('search', []) as $name => $params) {
                $this->register($name, $params);
            }

            $this->built = true;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): ?SearcherContract
    {
        return $this->searcher[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): ?Container
    {
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function register(string $name, $attrs = null): SearcherContract
    {
        $searcher = $attrs instanceof SearcherContract ? $attrs : (new Searcher())->setParams($attrs ?: []);

        return $this->searcher[$name] = $searcher->setName($name)->setManager($this);
    }
}
