<?php

return [
    'default' => [
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
        'post_status'     => 'any',
        /**
         * Autorise le court-circuitage des arguments contraints.
         * @var string[]
         */
        'allow_override'  => ['post_type']
    ]
];