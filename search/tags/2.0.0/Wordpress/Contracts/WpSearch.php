<?php declare(strict_types=1);

namespace tiFy\Plugins\Search\Wordpress\Contracts;

use tiFy\Plugins\Search\Contracts\Search;
use WP_Query;

interface WpSearch extends Search
{
    /**
     * Court-cicuitage de la requête principale de recherche par défaut.
     *
     * @param WP_Query $wp_query
     *
     * @return void
     */
    public function wpQueryDefault(WP_Query &$wp_query): void;
}