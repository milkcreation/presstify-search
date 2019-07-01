<?php declare(strict_types=1);

namespace tiFy\Plugins\Search\Wordpress;

use tiFy\Contracts\Container\Container;
use tiFy\Plugins\Search\Contracts\Search;

class Wordpress
{
    /**
     * Instance du conteneur d'injection de dépendances.
     * @var Container|null
     */
    protected $container;

    /**
     * Instance du gestionnaire de plugin.
     * @var Search
     */
    protected $manager;

    /**
     * CONSTRUCTEUR.
     *
     * @param Container|null $container
     *
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        add_action('after_setup_theme', function() {
            $this->manager = $this->getContainer()->get('search');

            $this->manager->initialize();
        });
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
}