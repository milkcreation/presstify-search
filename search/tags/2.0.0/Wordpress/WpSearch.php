<?php declare(strict_types=1);

namespace tiFy\Plugins\Search\Wordpress;

use tiFy\Plugins\Search\Contracts\{Search as BaseSearchContract, Searcher as SearcherContract};
use tiFy\Plugins\Search\Search as BaseSearch;
use tiFy\Plugins\Search\Wordpress\Contracts\{WpSearch as WordpressContract, WpSearcher as WpSearcherContract};
use tiFy\Support\Proxy\Taxonomy;
use WP_Query;

class WpSearch extends BaseSearch implements WordpressContract
{
    /**
     * @inheritDoc
     */
    public function build(): BaseSearchContract
    {
        if (!$this->built) {
            parent::build();

            add_action('init', function () {
                Taxonomy::register('search_tag', [
                    'singular'          => __('mot-clef de recherche', 'tify'),
                    'plural'            => __('mots-clefs de recherche', 'tify'),
                    'object_type'       => ['post'],
                    'hierarchical'      => false,
                    'show_in_rest'      => true,
                    'show_in_menu'      => true,
                    'show_admin_column' => true,
                ]);

                foreach ($this->searcher as $name => $searcher) {
                    $searcher->build();
                }
            });

            add_action('init', function () {
                foreach ($this->searcher as $name => $searcher) {
                    $searcher->build();
                }
            }, 999999);

            add_filter('query_vars', function (array $query_vars) {
                $query_vars[] = 'searcher';
                $query_vars[] = '_s';

                return $query_vars;
            });

            add_action('pre_get_posts', [$this, 'wpQueryDefault'], 0);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function register(string $name, $attrs = null): SearcherContract
    {
        $searcher = $attrs instanceof WpSearcherContract ? $attrs : (new WpSearcher())->setParams($attrs ?: []);

        return $this->searcher[$name] = $searcher->setName($name)->setManager($this);
    }

    /**
     * @inheritDoc
     */
    public function wpQueryDefault(WP_Query &$wp_query): void
    {
        if ($wp_query->get('searcher', '')) {
            return;
        } elseif (!$this->get('default')) {
            return;
        } elseif (is_admin() && !defined('DOING_AJAX')) {
            return;
        } elseif (!$wp_query->is_main_query()) {
            return;
        } elseif (!$wp_query->is_search()) {
            return;
        }

        $wp_query->set('searcher', 'default');

        remove_filter(current_filter(), __METHOD__, 0);
    }
}