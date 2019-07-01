<?php declare(strict_types=1);

namespace tiFy\Plugins\Search;

use Psr\Container\ContainerInterface as Container;

/**
 * Class Search
 *
 * @desc Extension PresstiFy de recherche avancée.
 * @author Jordy Manner <jordy@milkcreation.fr>
 * @package tiFy\Plugins\Search
 * @version 2.0.0
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
class Search
{
    /**
     * Instance du conteneur d'injection de dépendances.
     * @var Container|null
     */
    protected $container;

    /**
     * CONSTRUCTEUR.
     *
     * @param Container|null $container
     *
     * @return void
     */
    public function __construct(?Container $container)
    {
        $this->container = $container;
    }
}
