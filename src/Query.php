<?php

namespace Stanejoun\FlamePHP;

class Query
{
	private string $select = '*';
	private string $from = '';
	private string $join = '';
	private string $where = '';
	private string $groupBy = '';
	private string $having = '';
	private string $orderBy = '';
	private string $limit = '';
	private array $args = [];

	public function select(string $select): Query
	{
		$this->select = $select;
		return $this;
	}

	public function from(string $from): Query
	{
		$this->from = $from;
		return $this;
	}

	public function join(string $join): Query
	{
		$this->join = $join;
		return $this;
	}

	public function where(string $where): Query
	{
		$this->where = $where;
		return $this;
	}

	public function groupBy(string $groupBy): Query
	{
		$this->groupBy = $groupBy;
		return $this;
	}

	public function having(string $having): Query
	{
		$this->having = $having;
		return $this;
	}

	public function orderBy(string $orderBy): Query
	{
		$this->orderBy = $orderBy;
		return $this;
	}

	public function limit(string $limit): Query
	{
		$this->limit = $limit;
		return $this;
	}

	public function args(array $args): Query
	{
		$this->args = $args;
		return $this;
	}

	public function getSelect(): string
	{
		return $this->select;
	}

	public function getFrom(): string
	{
		return $this->from;
	}

	public function getJoin(): string
	{
		return $this->join;
	}

	public function getWhere(): string
	{
		return $this->where;
	}

	public function getGroupBy(): string
	{
		return $this->groupBy;
	}

	public function getHaving(): string
	{
		return $this->having;
	}

	public function getOrderBy(): string
	{
		return $this->orderBy;
	}

	public function getLimit(): string
	{
		return $this->limit;
	}

	public function getArgs(): array
	{
		return $this->args;
	}
}
