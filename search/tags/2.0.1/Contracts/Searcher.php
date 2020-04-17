<?php declare(strict_types=1);

namespace tiFy\Plugins\Search\Contracts;

interface Searcher
{
    /**
     * Initialisation.
     *
     * @return static
     */
    public function build(): Searcher;

    /**
     * Liste des paramètres par défaut.
     *
     * @return array
     */
    public function defaults(): array;

    /**
     * Récupération du nombre de résultats trouvés pour une requête de recherche.
     *
     * @param int $group
     *
     * @return int
     */
    public function getFounds(int $group = 0): int;

    /**
     * Récupération de la liste des paramètres de configuration des groupes uniquement.
     * {@internal Le groupe 0 étant considéré comme un "non-groupe".}
     *
     * @return array|null
     */
    public function getGroupOnlyParams(): ?array;

    /**
     * Récupération du nom de qualification.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Vérification d'existance de resultats de recherche groupés.
     * {@internal Le groupe 0 étant considéré comme un "non-groupe".}
     *
     * @return bool
     */
    public function hasGroup(): bool;

    /**
     * Récupération de l'instance du gestionnaire de recherche.
     *
     * @return Search|null
     */
    public function manager(): ?Search;

    /**
     * Récupération de paramètres de configuration d'un groupe de recherche.
     *
     * @param string|null $key Clé d'indice du paramètres. Tous si null.
     * @param mixed $default Valeur de retour par défaut de l'attribut
     * @param int $group
     *
     * @return mixed
     */
    public function params(?string $key = null, $default = '', int $group = 0);

    /**
     * Définition de l'instance du gestionnaire de recherche.
     *
     * @param Search
     *
     * @return static
     */
    public function setManager(Search $manager): Searcher;

    /**
     * Définition du nom de qualification.
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): Searcher;

    /**
     * Définition de la liste des paramètres.
     *
     * @param array $params
     *
     * @return static
     */
    public function setParams(array $params): Searcher;
}