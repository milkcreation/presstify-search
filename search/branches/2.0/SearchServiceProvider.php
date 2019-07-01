<?php declare(strict_types=1);

namespace tiFy\Plugins\Search;

use tiFy\Container\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Liste des noms de qualification des services fournis.
     * @internal requis. Tous les noms de qualification de services à traiter doivent être renseignés.
     * @var string[]
     */
    protected $provides = [
        'search'
    ];

    /**
     * @inheritDoc
     */
    public function boot()
    {
        add_action('after_setup_theme', function() {
            $this->getContainer()->get('search');
        });
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
        $this->getContainer()->share('search', function() {
            return new Search($this->getContainer());
        });
    }
}