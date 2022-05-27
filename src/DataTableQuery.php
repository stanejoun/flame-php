<?php

namespace Stanejoun\FlamePHP;

class DataTableQuery
{
	public int $page = 1;
	public int $limit = 10;
	public string $search = '';
	public array $sort = [];
	public Query $query;
	public mixed $select = '*';
	public string $from = '';
	#[ArrayOf('String')]
	public array $join = [];
	public array $searchClauses = [];
	#[ArrayOf('DataTableFilter')]
	public array $filters = [];
	#[ArrayOf('String')]
	public array $filtersOn = [];
	#[ArrayOf('String')]
	public array $sortOn = [];
	public array $where = [];


	public function __construct(mixed $select, string $from, array $join = [], array $searchClauses = [], array $filtersOn = [], array $where = [], array $sortOn = [])
	{
		$this->query = new Query();
		$this->select = $select;
		$this->from = $from;
		$this->join = $join;
		$this->searchClauses = $searchClauses;
		$this->filtersOn = $filtersOn;
		$this->sortOn = $sortOn;
		$this->where = $where;
		$this->fetchRequestData();
		$this->build();
	}

	public function fetchRequestData()
	{
		$data = Request::data();
		if (empty($data)) {
			throw new \Exception('Error: empty post data! Unable to fetch the data table.');
		}
		$this->search = isset($data['search']) ? trim($data['search']) : '';
		$this->sort = $data['sort'] ?? [];
		$this->page = $data['page'] ?? 1;
		$this->limit = $data['limit'] ?? 10;
		$this->filters = $data['filters'] ?? [];
	}

	public function build(): self
	{
		if ($this->search && !empty($this->searchClauses)) {
			$where = implode(' OR ', $this->searchClauses);
			$this->query->where($where);
			$args = [':search' => "%{$this->search}"];
			if (str_contains($where, ':searchEncrypted')) {
				$args[':searchEncrypted'] = Security::encrypt($this->search);
			}
			$this->query->args($args);
		}

		if (!empty($this->sort) && !empty($this->sortOn)) {
			$orderByArray = [];
			foreach ($this->sortOn as $column) {
				if (isset($this->sort[$column]) && $this->sort[$column] !== 'none' && in_array(strtolower($this->sort[$column]), ['asc', 'desc'])) {
					$orderByArray[] = "$column {$this->sort[$column]}";
				}
			}
			if (!empty($orderByArray)) {
				$orderBy = implode(', ', $orderByArray);
				$this->query->orderBy($orderBy);
			}
		}

		$this->buildFiltersClauses();

		return $this;
	}

	public function buildFiltersClauses()
	{
		if (!empty($this->filtersOn) && !empty($this->filters)) {
			$filterClause = [];
			$args = [];
			foreach ($this->filters as $filterData) {
				/** @var DataTableFilter $filter */
				$filter = helper::instantiate(DataTableFilter::class, $filterData);
				if (in_array($filter->column, $this->filtersOn)) {
					$filterColumn = $filter->column;
					if ($filter->type === DataTableFilter::DATE_TYPE) {
						if ($filter->dateFrom) {
							$filterClause[] = "$filterColumn >= :filter_date_from";
							$args['filter_date_from'] = date('Y-m-d 00:00:00', strtotime($filter->dateFrom));
						}
						if ($filter->dateTo) {
							$filterClause[] = "$filterColumn <= :filter_date_to";
							$args['filter_date_to'] = date('Y-m-d 23:59:59', strtotime($filter->dateTo));
						}
					} else if ($filter->type === DataTableFilter::PERIOD_TYPE) {
						switch ($filter->period) {
							case 'today':
								$filterClause[] = "$filterColumn >= :filter_date_from AND $filterColumn <= :filter_date_to";
								$args['filter_date_from'] = date('Y-m-d 00:00:00', strtotime('now'));
								$args['filter_date_to'] = date('Y-m-d 23:59:59', strtotime('now'));
								break;
							case 'yesterday':
								$filterClause[] = "$filterColumn >= :filter_date_from AND $filterColumn <= :filter_date_to";
								$args['filter_date_from'] = date('Y-m-d 00:00:00', strtotime('previous day'));
								$args['filter_date_to'] = date('Y-m-d 23:59:59', strtotime('previous day'));
								break;
							case 'currentWeek':
								$filterClause[] = "$filterColumn >= :filter_date_from AND $filterColumn<= :filter_date_to";
								$args['filter_date_from'] = date('Y-m-d 00:00:00', strtotime('monday this week'));
								$args['filter_date_to'] = date('Y-m-d 23:59:59', strtotime('sunday this week'));
								break;
							case 'lastWeek':
								$filterClause[] = "$filterColumn >= :filter_date_from AND $filterColumn <= :filter_date_to";
								$args['filter_date_from'] = date('Y-m-d 00:00:00', strtotime('monday previous week'));
								$args['filter_date_to'] = date('Y-m-d 23:59:59', strtotime('sunday previous week'));
								break;
							case 'currentMonth':
								$filterClause[] = "$filterColumn >= :filter_date_from AND $filterColumn <= :filter_date_to";
								$args['filter_date_from'] = date('Y-m-d 00:00:00', strtotime('first day of this month'));
								$args['filter_date_to'] = date('Y-m-d 23:59:59', strtotime('last day of this month'));
								break;
							case 'lastMonth':
								$filterClause[] = "$filterColumn >= :filter_date_from AND $filterColumn <= :filter_date_to";
								$args['filter_date_from'] = date('Y-m-d 00:00:00', strtotime('first day of previous month'));
								$args['filter_date_to'] = date('Y-m-d 23:59:59', strtotime('last day of previous month'));
								break;
							case 'currentYear':
								$filterClause[] = "year($filterColumn) = :filter_date";
								$args['filter_date'] = date('Y', strtotime('this year'));
								break;
							case 'lastYear':
								$filterClause[] = "year($filterColumn) = :filter_date";
								$args['filter_date'] = date('Y', strtotime('previous year'));
								break;
							case '-24 hours':
							case '-7 days':
							case '-30 days':
								$filterClause[] = "$filterColumn >= :filter_date_from AND $filterColumn <= :filter_date_to";
								$args['filter_date_from'] = date('Y-m-d H:i:s', strtotime($filter->period));
								$args['filter_date_to'] = date('Y-m-d H:i:s', strtotime('now'));
								break;
							case '-3 months':
							case '-6 months':
							case '-12 months':
								$filterClause[] = "$filterColumn >= :filter_date_from AND $filterColumn <= :filter_date_to";
								$args['filter_date_from'] = date('Y-m-d 00:00:00', strtotime("first day of {$filter->period}"));
								$args['filter_date_to'] = date('Y-m-d 23:59:59', strtotime('now'));
								break;
						}
					} else if ($filter->type === DataTableFilter::LIST_TYPE || $filter->type === DataTableFilter::BUTTON_TYPE) {
						if (is_array($filter->selectedValues) && !empty($filter->selectedValues)) {
							$filterClause[] = "$filterColumn IN(:filter_{$filter->column})";
							if ($filter->isEncryptedField) {
								foreach ($filter->selectedValues as $index => $selectedValue) {
									$filter->selectedValues[$index] = Security::encrypt($selectedValue);
								}
							}
							$args["filter_{$filter->column}"] = $filter->selectedValues;
						} else if ($filter->isSerializedData) {
							$filterClause[] = "{$filter->column} LIKE :filter_{$filter->column}";
							foreach ($filter->selectedValues as $selectedValue) {
								$args["filter_{$filter->column}"] = "%i:$selectedValue;%";
							}
						} else if (!empty($filter->selectedValues)) {
							$filterClause[] = "$filterColumn = :filter_{$filter->column}";
							if ($filter->isEncryptedField) {
								$filter->selectedValues = Security::encrypt($filter->selectedValues);
							}
							$args["filter_{$filter->column}"] = $filter->selectedValues;
						}
					}
				}
			}
			if (!empty($filterClause) && !empty($args)) {
				$where = $this->query->getWhere();
				if (!empty($where)) {
					$where .= ' AND (';
				} else {
					$where = '(';
				}
				$where .= implode(' AND ', $filterClause) . ')';
				$this->query->where($where);
				$currentArgs = $this->query->getArgs();
				if (!empty($currentArgs)) {
					$args = array_merge($currentArgs, $args);
				}
				$this->query->args($args);
			}
		}
	}

	public function search(): SearchResult
	{
		if (is_array($this->select)) {
			$this->query->select(implode(', ', $this->select));
		} else {
			$this->query->select($this->select);
		}
		$this->query->join(implode(' ', $this->join));
		return call_user_func([$this->from, 'search'], $this->query, $this->page, $this->limit);
	}
}