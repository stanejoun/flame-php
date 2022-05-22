<?php

namespace Stanejoun\LightPHP;

class DataTableResult extends SearchResult
{
	/** @var DataTableFilter[] */
	#[ArrayOf('\Stanejoun\LightPHP\DataTableFilter')]
	public array $filters = [];

	public function __construct(SearchResult $searchResult, array $filters) {
		$this->total = $searchResult->total;
		$this->items = $searchResult->items;
		$this->start = $searchResult->start;
		$this->limit = $searchResult->limit;
		$this->numberOfPages = $searchResult->numberOfPages;
		$this->page = $searchResult->page;
		$this->filters = $filters;
	}
}