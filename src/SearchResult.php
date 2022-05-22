<?php

namespace Stanejoun\LightPHP;

class SearchResult
{
	public int $total = 0;
	public array $items = [];
	public int $start = 1;
	public int $limit = 15;
	public int $numberOfPages = 0;
	public int $page = 1;
}