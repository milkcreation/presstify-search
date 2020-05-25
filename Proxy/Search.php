<?php declare(strict_types=1);

namespace tiFy\Plugins\Search\Proxy;

use tiFy\Support\Proxy\AbstractProxy;
use tiFy\Plugins\Search\Contracts\{Search as SearchContract, Searcher as SearcherContract};

/**
 * @method static SearcherContract|null get(string $name)
 * @method static SearcherContract|null register(string $name, array|SearcherContract|null $args = null)
 *
 * @see \tiFy\Plugins\Search\Search
 */
class Search extends AbstractProxy
{
    /**
     * {@inheritDoc}
     *
     * @return SearchContract
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * @inheritDoc
     */
    public static function getInstanceIdentifier()
    {
        return 'search';
    }
}