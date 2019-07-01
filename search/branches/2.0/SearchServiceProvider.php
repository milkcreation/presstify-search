<?php declare(strict_types=1);

namespace tiFy\Plugins\Search;

use tiFy\Container\ServiceProvider;
use tiFy\Plugins\Search\Wordpress\Wordpress;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Liste des noms de qualification des services fournis.
     * @internal requis. Tous les noms de qualification de services à traiter doivent être renseignés.
     * @var string[]
     */
    protected $provides = [
        'search',
        'search.wordpress'
    ];

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        $this->getContainer()->share('search.wordpress', function() {
            return new Wordpress($this->getContainer());
        });

        if (($wp = $this->getContainer()->get('wp')) && $wp->is()) {
            $this->getContainer()->get('search.wordpress');
        }
    }

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->getContainer()->share('search', function() {
            return (new Search($this->getContainer()));
        });
    }
}