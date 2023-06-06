<?php

namespace MoloniES\Services\Exports;

abstract class ExportService
{
    /**
     * @var array
     */
    protected $syncedProducts = [];

    /**
     * @var array
     */
    protected $errorProducts = [];

    /**
     * @var int
     */
    protected $totalResults = 0;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $itemsPerPage = 20;

    public function __construct(?int $page = 1)
    {
        $this->page = $page;
    }

    public function getHasMore(): bool
    {
        return $this->totalResults === $this->itemsPerPage;
    }

    public function getProcessedProducts()
    {
        return (($this->page - 1) * $this->itemsPerPage) + $this->totalResults;
    }


    public function getErrorProducts(): array
    {
        return $this->errorProducts;
    }

    public function getSyncedProducts(): array
    {
        return $this->syncedProducts;
    }

    abstract public function run();
}
