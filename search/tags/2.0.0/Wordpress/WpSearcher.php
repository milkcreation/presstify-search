<?php declare(strict_types=1);

namespace tiFy\Plugins\Search\Wordpress;

use Illuminate\Support\Collection;
use tiFy\Plugins\Search\Contracts\Searcher as BaseSearcherContract;
use tiFy\Plugins\Search\Searcher as BaseSearcher;
use tiFy\Plugins\Search\Wordpress\Contracts\WpSearcher as WpSearcherContract;
use tiFy\Support\{Arr, Proxy\Request};
use WP_Post, WP_Query;

class WpSearcher extends BaseSearcher implements WpSearcherContract
{
    /**
     * Liste des clés d'indices des champs (colonne) de recherche par groupe de recherche.
     * {@internal Si true utilise les indices des champs de recherche natifs [post_title, post_content, post_excerpt].}
     * @var string[][]
     */
    protected $fields = [];

    /**
     * Liste des clés d'indices des metadonnées de recherche par groupe de recherche.
     * @var string[][]
     */
    protected $metas = [];

    /**
     * Liste des indices de qualification des publications trouvées par groupe de recherche.
     * @var int[][]
     */
    protected $postIds = [];

    /**
     * Liste des variable de requêtes par groupe de recherche.
     * @var array[]
     */
    protected $queryVars = [];

    /**
     * Liste des stopwords (Mots vide) utilisés lors du traitement de la recherche.
     * @var string[]
     */
    protected $stopwords = [];

    /**
     * Liste des clés d'indices des type de mots clefs de (taxonomies) recherche par groupe de recherche.
     * {@internal Si true utilise l'indice de mot clé de recherche natif [search_tag].}
     * @var string[][]
     */
    protected $tags = [];

    /**
     * @inheritDoc
     */
    public function build(): BaseSearcherContract
    {
        if (!$this->built) {
            parent::build();

            $tagPostTypes = [];

            (new Collection($this->params))->each(function ($params) use (&$tagPostTypes) {
                if (!isset($params['post_type'])) {
                    return;
                } elseif (empty($params['searcher_tags'])) {
                    return;
                }

                if($params['searcher_tags'] === true) {
                    $tagPostTypes = array_merge($tagPostTypes, Arr::wrap($params['post_type']));
                } elseif (in_array('search_tag', $params['searcher_tags'])) {
                    $tagPostTypes = array_merge($tagPostTypes, Arr::wrap($params['post_type']));
                }

                array_unique($tagPostTypes);
            });

            foreach($tagPostTypes as $postType) {
                register_taxonomy_for_object_type('search_tag', $postType);
            }

            add_action('pre_get_posts', [$this, 'wpQueryPreGetPosts'], 999999);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function defaults(): array
    {
        return [
            /**
             * Liste des champs de recherche dans la table principale.
             * {@internal Si true, utilise les valeurs par défaut de Wordpress : ['post_title', 'post_content', 'post_excerpt'].}
             * @var bool|string|string[]
             */
            'searcher_fields' => true,
            /**
             * Liste des clés d'indice de métadonnées (meta_key) de recherche.
             * {@internal Non opérant si true.}
             * @var false|string|string[]
             */
            'searcher_metas'  => false,
            /**
             * Liste des indice de qualification des mots clés (taxonomie) de recherche.
             * {@internal Très consommateur en mémoire. Si true utilise les mots clefs par défaut : ['search_tag']}
             * @var bool|string|string[]
             */
            'searcher_tags'   => false,
            /**
             * Liste des types de publication contraints à la recherche.
             * @var string|string[]
             */
            'post_type'       => 'any',
            /**
             * Liste des status de publication contraints de la recherche.
             * @var string|string[]
             */
            'post_status'     => 'publish',
            /**
             * Autorise le court-circuitage des arguments contraints.
             * @var string[]
             */
            'allow_override'  => ['post_type']
        ];
    }

    /**
     * Retrouve les stopwords (mots vides) utilisés lors du traitement de la recherche.
     * @return string[]
     * @see WP_Query::get_search_stopwords()
     *
     */
    protected function getSearchStopwords(): array
    {
        if (isset($this->stopwords)) {
            return $this->stopwords;
        }

        $words = explode(',', _x(
            'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
            'Comma-separated list of search stopwords in your language'
        ));

        $stopwords = [];
        foreach ($words as $word) {
            $word = trim($word, "\r\n\t ");
            if ($word) {
                $stopwords[] = $word;
            }
        }

        return $this->stopwords = apply_filters('wp_search_stopwords', $stopwords);
    }

    /**
     * Filtrage des conditions de requête.
     *
     * @param array $clauses
     * @param int|null $group Indice de qualification du groupe
     * @param WP_Query $wp_query
     *
     * @return array
     */
    protected function parseClauses(array $clauses, ?int $group, WP_Query &$wp_query): array
    {
        global $wpdb;

        $group = $group ?? 0;

        /**
         * Extraction des conditions de requête
         * @var string $where
         * @var string $groupby
         * @var string $join
         * @var string $orderby
         * @var string $distinct
         * @var string $fields
         * @var string $limits
         */
        extract($clauses);

        if ($group) {
            // Traitement des conditions de requêtes induites par les taxonomies
            $wp_query->parse_tax_query($this->queryVars[$group]);
            $tax_clauses = $wp_query->tax_query->get_sql($wpdb->posts, 'ID');
            $join .= $tax_clauses['join'];
            $where .= $tax_clauses['where'];
        }

        $where .= $this->parseSearch($this->queryVars[$group], $group, $wp_query);

        if (!empty($this->metas[$group])) {
            foreach ($this->metas[$group] as $i => $meta_key) {
                $join .= " LEFT OUTER JOIN {$wpdb->postmeta} as tfys_meta_g{$group}i{$i} ON ({$wpdb->posts}.ID = tfys_meta_g{$group}i{$i}.post_id AND tfys_meta_g{$group}i{$i}.meta_key = '{$meta_key}')";
            }
        }

        if (!empty($this->tags[$group])) {
            foreach ($this->tags[$group] as $i => $tag) {
                $join .= " LEFT OUTER JOIN {$wpdb->term_relationships} AS tfys_tmr_g{$group}i{$i} ON ({$wpdb->posts}.ID = tfys_tmr_g{$group}i{$i}.object_id)";
                $join .= " LEFT OUTER JOIN {$wpdb->term_taxonomy} AS tfys_tmt_g{$group}i{$i} ON (tfys_tmr_g{$group}i{$i}.term_taxonomy_id = tfys_tmt_g{$group}i{$i}.term_taxonomy_id  AND tfys_tmt_g{$group}i{$i}.taxonomy = '{$tag}')";
                $join .= " LEFT OUTER JOIN {$wpdb->terms} AS tfys_tms_g{$group}i{$i} ON (tfys_tmt_g{$group}i{$i}.term_id = tfys_tms_g{$group}i{$i}.term_id)";
            }
        }

        if ($this->queryVars[$group]['searcher_metas'] || $this->queryVars[$group]['searcher_tags']) {
            $groupby = "{$wpdb->posts}.ID";
        }

        return compact('where', 'groupby', 'join', 'orderby', 'distinct', 'fields', 'limits');
    }

    /**
     * Pré-Traitement des variables de requêtes.
     *
     * @param int|null $group Indice de qualification du groupe.
     * @param WP_Query $wp_query
     *
     * @return array
     */
    protected function parseQueryVars(?int $group, WP_Query &$wp_query): array
    {
        $group = $group ?? 0;
        $queryVars = [];

        if (!$attrs = $this->params(null, '', $group ?: 0)) {
            $attrs = [];
        }

        foreach ($attrs as $key => $value) {
            if (in_array($key, ['searcher_fields', 'searcher_metas', 'searcher_tags'])) {
                $queryVars[$key] = $value;
            }
        }

        if (isset($attrs['allow_override'])) {
            $wp_query->parse_query();

            foreach((array) $attrs['allow_override'] as $key) {
                if ($value = Request::input($key)) {
                    $attrs[$key] = $value;
                }
            }
        }

        foreach ($wp_query->fill_query_vars($attrs) as $k => $v) {
            if (isset($attrs[$k])) {
                $queryVars[$k] = $v;
            }
        }

        return $this->queryVars[$group] = array_merge($wp_query->query_vars, $queryVars);
    }

    /**
     * Traitement de la requête de recherche
     *
     * @param array $q Variables de requête.
     * @param int $group Indice de qualification du groupe.
     * @param WP_Query $wp_query
     *
     * @return string
     * @see WP_Query::parse_search()
     *
     */
    protected function parseSearch(array &$q, ?int $group, WP_Query &$wp_query): string
    {
        global $wpdb;

        $group = $group ?? 0;
        $search = '';

        if (!empty($q['_s'])) {
            $q['s'] = stripslashes($q['_s']);

            if (empty($_GET['_s']) && $wp_query->is_main_query()) {
                $q['s'] = urldecode($q['s']);
            }
        } else {
            $q['s'] = stripslashes($q['s']);

            if (empty($_GET['s']) && $wp_query->is_main_query()) {
                $q['s'] = urldecode($q['s']);
            }
        }

        $q['s'] = str_replace(["\r", "\n"], '', $q['s']);

        $q['search_terms_count'] = 1;
        if (!empty($q['sentence'])) {
            $q['search_terms'] = [$q['s']];
        } else {
            /*if (preg_match_all('/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $q['s'], $matches)) {
                $q['search_terms_count'] = count($matches[0]);
                $q['search_terms'] = $this->parseSearchTerms($matches[0]);

                // if the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence
                if (empty($q['search_terms']) || count($q['search_terms']) > 9) {
                    $q['search_terms'] = [$q['s']];
                }
            } else {*/
            $q['search_terms'] = [$q['s']];
            //}
        }

        $n = !empty($q['exact']) ? '' : '%';
        $searchand = '';
        $q['search_orderby_title'] = [];

        $exclusion_prefix = apply_filters('wp_query_search_exclusion_prefix', '-');

        foreach ($q['search_terms'] as $term) {
            $exclude = $exclusion_prefix && ($exclusion_prefix === substr($term, 0, 1));

            if ($exclude) {
                $like_op = 'NOT LIKE';
                $andor_op = 'AND';
                $term = substr($term, 1);
            } else {
                $like_op = 'LIKE';
                $andor_op = 'OR';
            }

            if ($n && !$exclude) {
                $like = '%' . $wpdb->esc_like($term) . '%';
                $q['search_orderby_title'][] = $wpdb->prepare("{$wpdb->posts}.post_title LIKE %s", $like);
            }

            $like = $n . $wpdb->esc_like($term) . $n;

            $search_parts = [];
            $search_parts_args = [];

            if ($fields = $q['searcher_fields']) {
                if (is_bool($fields)) {
                    $this->fields[$group] = ['post_title', 'post_content', 'post_excerpt'];
                } else {
                    $this->fields[$group] = Arr::wrap($fields);
                }

                foreach ($this->fields[$group] as $field) {
                    $search_parts[] = "({$wpdb->posts}.{$field} {$like_op} %s)";
                    $search_parts_args[] = $like;
                }
            }

            if ($metas = $q['searcher_metas']) {
                if (is_bool($metas)) {
                    $this->metas[$group][] = [];
                } else {
                    $this->metas[$group] = Arr::wrap($metas);
                }

                foreach ($this->metas[$group] as $i => $meta) {
                    $search_parts[] = "(tfys_meta_g{$group}i{$i}.meta_value {$like_op} %s)";
                    $search_parts_args[] = $like;
                }
            }

            if ($tags = $q['searcher_tags']) {
                if (is_bool($tags)) {
                    $this->tags[$group][] = 'search_tag';
                } else {
                    $this->tags[$group] = Arr::wrap($tags);
                }

                foreach ($this->tags[$group] as $i => $tag) {
                    $search_parts[] = "(tfys_tms_g{$group}i{$i}.name {$like_op} %s)";
                    $search_parts_args[] = $like;
                }
            }

            if ($search_parts) {
                $_search_parts = implode(" {$andor_op} ", $search_parts);
                array_unshift($search_parts_args, $_search_parts);
                $search .= call_user_func_array([$wpdb, 'prepare'], $search_parts_args);
            }

            if ($search) {
                $search = "{$searchand}({$search})";
            }

            $searchand = ' AND ';
        }

        if (!empty($search)) {
            $search = " AND ({$search})";

            if ($search_post_types = $this->parseSearchPostTypes($q, $group, $wp_query)) {
                $search .= $search_post_types;
            }

            if (!is_user_logged_in()) {
                $search .= " AND ({$wpdb->posts}.post_password = '') ";
            }
        }

        return $search;
    }

    /**
     * Check if the terms are suitable for searching.
     *
     * Uses an array of stopwords (terms) that are excluded from the separate
     * term matching when searching for posts. The list of English stopwords is
     * the approximate search engines list, and is translatable.
     *
     * @param string[] $terms Array of terms to check.
     *
     * @return string[] Terms that are not stopwords.
     * @since 3.7.0
     *
     * @see WP_Query::parse_search_terms()
     */
    protected function parseSearchTerms(array $terms)
    {
        $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
        $checked = [];

        $stopwords = $this->getSearchStopwords();

        foreach ($terms as $term) {
            // Keep before/after spaces when term is for exact match.
            if (preg_match('/^".+"$/', $term)) {
                $term = trim($term, "\"'");
            } else {
                $term = trim($term, "\"' ");
            }

            // Avoid single A-Z and single dashes.
            if (!$term || (1 === strlen($term) && preg_match('/^[a-z\-]$/i', $term))) {
                continue;
            }

            if (in_array(call_user_func($strtolower, $term), $stopwords, true)) {
                continue;
            }

            $checked[] = $term;
        }

        return $checked;
    }

    /**
     * Traitement des types de post de la requête de recherche
     *
     * @param array $q Variables de requête
     * @param int|null $group Index d'identification du groupe
     * @param WP_Query $wp_query
     *
     * @return string
     */
    protected function parseSearchPostTypes(&$q, ?int $group, WP_Query &$wp_query): string
    {
        global $wpdb;

        $where = "";
        $post_type = (isset($q['post_type'])) ? $q['post_type'] : 'any';

        if ($post_type === $wp_query->get('post_type')) {
            return $where;
        }

        if ('any' == $post_type) {
            $in_search_post_types = get_post_types(['exclude_from_search' => false]);

            if (empty($in_search_post_types)) {
                $where .= " AND 1=0 ";
            } else {
                $where .= " AND {$wpdb->posts}.post_type IN" .
                    " ('" . join("', '", array_map('esc_sql', $in_search_post_types)) . "')";
            }
        } elseif (!empty($post_type) && is_array($post_type)) {
            $where .= " AND {$wpdb->posts}.post_type IN ('" . join("', '", esc_sql($post_type)) . "')";
        } else {
            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_type = %s", $post_type);
        }

        return $where;
    }

    /**
     * Filtrage du total de résultats trouvés.
     *
     * @param int $found_posts
     * @param WP_Query $wp_query
     *
     * @return int
     *
     * @see WP_Query::set_found_posts()
     */
    public function wpQueryFoundPosts(int $found_posts, WP_Query $wp_query): int
    {
        if (!$this->hasGroup()) {
            $this->founds[0] = $found_posts;
        }

        remove_filter(current_filter(), __METHOD__, 10);

        return $found_posts;
    }

    /**
     * Filtrage des conditions de requêtes.
     *
     * @param array $clauses
     * @param WP_Query $wp_query
     *
     * @return array
     *
     * @see WP_Query::get_posts()
     */
    public function wpQueryPostsClauses(array $clauses, WP_Query $wp_query): array
    {
        global $wpdb;

        if (!$groups_attrs = $this->getGroupOnlyParams()) {
            $clauses = $this->parseClauses($clauses, 0, $wp_query);
        } else {
            $group_clauses = [];
            $group_query = [];
            $group_ids = '';

            foreach ($groups_attrs as $i => $group_attrs) {
                $this->parseQueryVars($i, $wp_query);

                $gc = $this->parseClauses($clauses, $i, $wp_query);
                $group_clauses[] = $gc;

                if (!empty($gc['groupby'])) {
                    $gc['groupby'] = 'GROUP BY ' . $gc['groupby'];
                }

                if (!empty($gc['orderby'])) {
                    $gc['orderby'] = 'ORDER BY ' . $gc['orderby'];
                }

                if (!$this->founds[$i] = (int)$wpdb->get_var(
                    "SELECT COUNT(DISTINCT {$wpdb->posts}.ID)" .
                    " FROM {$wpdb->posts} {$gc['join']} WHERE 1 {$gc['where']}"
                )) {
                    continue;
                }

                $this->postIds[$i] = $wpdb->get_col(
                    "SELECT {$gc['distinct']}" .
                    " {$wpdb->posts}.ID FROM {$wpdb->posts} {$gc['join']}" .
                    " WHERE 1 {$gc['where']} {$gc['groupby']} {$gc['orderby']} {$gc['limits']}"
                );
                $group_ids .= join(',', $this->postIds[$i]);

                $group_query[$i] = "({$wpdb->posts}.ID IN (" . join(',', $this->postIds[$i]) .
                    ") AND @tFySearchGroup:=if({$wpdb->posts}.ID, {$i}, 0))";
            }

            /**
             * @var string $where
             * @var string $groupby
             * @var string $join
             * @var string $orderby
             * @var string $distinct
             * @var string $fields
             * @var string $limits
             */
            extract($clauses);

            $where = " AND (" . join(" OR ", $group_query) . ")";
            $groupby = "";
            $join = "";
            $orderby = "@tFySearchGroup ASC" . ($group_ids ? ", FIELD({$wpdb->posts}.ID, {$group_ids})" : "");
            $distinct = "";
            $fields .= ", @tFySearchGroup as tFySearchGroup";
            $limits = "";

            $clauses = compact('where', 'groupby', 'join', 'orderby', 'distinct', 'fields', 'limits');

            add_filter('posts_pre_query', [$this, 'wpQueryPostsPreQuery'], 10, 2);
        }

        add_filter('posts_request', [$this, 'wpQueryPostsRequest'], 10, 2);
        add_filter('the_posts', [$this, 'wpQueryThePosts'], 10, 2);
        add_filter('found_posts', [$this, 'wpQueryFoundPosts'], 10, 2);

        remove_filter(current_filter(), __METHOD__, 10);

        return $clauses;
    }

    /**
     * Préfiltrage de la liste des posts.
     *
     * @param WP_Post[]|null $posts
     * @param WP_Query $wp_query
     *
     * @return WP_Post[]|array|null
     *
     * @see WP_Query::get_posts()
     */
    public function wpQueryPostsPreQuery(?array $posts, WP_Query &$wp_query): ?array
    {
        global $wpdb;

        $wpdb->query("SET @tFySearchGroup:=0;");

        remove_filter(current_filter(), __METHOD__, 10);

        return $posts;
    }

    /**
     * Filtrage de la requête de récupération des contenus.
     * {@internal Filtre utilisé uniquement dans le cadre du déboguage.}
     *
     * @param string $request
     * @param WP_Query $wp_query
     *
     * @return string
     *
     * @see WP_Query::get_posts()
     */
    public function wpQueryPostsRequest(string $request, WP_Query $wp_query): string
    {
        remove_filter(current_filter(), __METHOD__, 10);

        return $request;
    }


    /**
     * Filtrage des conditions de requêtes de recherche.
     * {@internal Court-circuitage des conditions de recherche native de Wordpress.}
     *
     * @param string $search
     * @param WP_Query $wp_query
     *
     * @return string
     *
     * @see WP_Query::get_posts()
     */
    public function wpQueryPostsSearch(string $search, WP_Query $wp_query): string
    {
        remove_filter(current_filter(), __METHOD__, 10);

        return '';
    }

    /**
     * Pré-modifications de requête.
     *
     * @param WP_Query $wp_query
     *
     * @return void
     *
     * @see WP_Query::get_posts()
     */
    public function wpQueryPreGetPosts(WP_Query &$wp_query): void
    {
        if ($wp_query->get('searcher', '') !== $this->getName()) {
            return;
        }

        if (!$this->params('s')) {
            $this->params[0]['s'] = $wp_query->get('s', '');
        }

        $wp_query->query_vars = $this->parseQueryVars(0, $wp_query);

        add_filter('posts_search', [$this, 'wpQueryPostsSearch'], 10, 2);
        add_filter('posts_clauses', [$this, 'wpQueryPostsClauses'], 10, 2);

        remove_filter(current_filter(), __METHOD__, 999999);
    }

    /**
     * Filtrage de la liste des posts trouvés.
     * {@internal Filtre utilisé uniquement dans le cadre du déboguage.}
     *
     * @param WP_Post[] $posts
     * @param WP_Query $wp_query
     *
     * @return WP_Post[]|array
     *
     * @see WP_Query::get_posts()
     */
    public function wpQueryThePosts(array $posts, WP_Query $wp_query): array
    {
        remove_filter(current_filter(), __METHOD__, 10);

        return $posts;
    }
}