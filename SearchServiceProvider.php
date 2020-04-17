<?php declare(strict_types=1);

namespace tiFy\Plugins\Search;

use tiFy\Container\ServiceProvider;
use tiFy\Plugins\Search\Wordpress\WpSearch;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        if (($wp = $this->getContainer()->get('wp')) && $wp->is()) {
            $this->getContainer()->share('search', new WpSearch($this->getContainer()));

            add_action('after_setup_theme', function () {
                $this->getContainer()->get('search')->build();
            });
        } else {
            $this->getContainer()->share('search', new Search($this->getContainer()));
        }
    }
}