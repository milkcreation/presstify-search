<?php declare(strict_types=1);

namespace tiFy\Plugins\Search;

use tiFy\Plugins\Search\Contracts\{Search as SearchContract, Searcher as SearcherContract};

class Searcher implements SearcherContract
{
    /**
     * Inidicateur d'initialisation.
     * @var bool
     */
    protected $built = false;

    /**
     * Liste du total de publications trouvées selon le groupe de recherche.
     * @var int[]
     */
    protected $founds = [];

    /**
     * Instance du gestionnaire de recherche.
     * @var SearchContract|null
     */
    protected $manager;

    /**
     * Nom de qualification.
     * @var string
     */
    protected $name = '';

    /**
     * Liste des paramètres de configuration par groupe de recherche.
     * @var array
     */
    protected $params = [];

    /**
     * @inheritDoc
     */
    public function build(): SearcherContract
    {
        if (!$this->built) {
            $this->built = true;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function defaults(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getFounds(int $group = 0): int
    {
        return $this->founds[$group] ?? 0;
    }

    /**
     * @inheritDoc
     */
    public function getGroupOnlyParams(): ?array
    {
        if ($this->hasGroup()) {
            $params = $this->params;
            unset($params[0]);

            return $params;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function hasGroup(): bool
    {
        $params = $this->params;
        unset($params[0]);

        return !!count($params);
    }

    /**
     * @inheritDoc
     */
    public function manager(): ?SearchContract
    {
        return $this->manager;
    }

    /**
     * @inheritDoc
     */
    public function params(?string $key = null, $default = '', int $group = 0)
    {
        if (is_null($key)) {
            return $this->params[$group] ?? null;
        } elseif (isset($this->params[$group]) && isset($this->params[$group][$key])) {
            return $this->params[$group][$key];
        } else {
            return $default;
        }
    }

    /**
     * @inheritDoc
     */
    public function setManager(SearchContract $manager): SearcherContract
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): SearcherContract
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setParams(array $params): SearcherContract
    {
        if (isset($params[0])) {
            foreach($params as $i => $attrs) {
                if (is_numeric($i)) {
                    $this->params[$i] = array_merge($this->defaults(), $attrs);
                }
            }
        } else {
            $this->params[0] = array_merge($this->defaults(), $params);
        }

        return $this;
    }
}