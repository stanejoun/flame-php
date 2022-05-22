<?php

namespace Stanejoun\LightPHP;

class DataTableFilter
{
	const BUTTON_TYPE = 'button';
	const LIST_TYPE = 'list';
	const DATE_TYPE = 'date';
	const PERIOD_TYPE = 'period';

	public string $name = '';
	public string $column = '';
	public bool $isEncryptedField = false;
	public mixed $selectedValues = null;
	public string $dateFrom = '';
	public string $dateTo = '';
	public string $period = '';
	public string $type = '';

	public function __construct(string $name, string $column, bool $isEncryptedField = false)
	{
		$this->name = $name;
		$this->column = $column;
		$this->isEncryptedField = $isEncryptedField;
		$this->fetchRequestData();
	}

	public function fetchRequestData()
	{
		$filters = $this->getFilters();
		foreach ($filters as $filter) {
			if ($filter['name'] === $this->name) {
				if (isset($filter['selectedValues'])) {
					$this->selectedValues = $filter['selectedValues'];
				}
				if (isset($filter['dateFrom'])) {
					$this->dateFrom = !empty($filter['dateFrom']) ? $filter['dateFrom'] : '';
				}
				if (isset($filter['dateTo'])) {
					$this->dateTo = !empty($filter['dateTo']) ? $filter['dateTo'] : '';
				}
				if (isset($filter['period'])) {
					$this->period = $filter['period'];
				}
			}
		}
		if ($this->isEncryptedField && $this->selectedValues !== null) {
			if (is_array($this->selectedValues)) {
				foreach ($this->selectedValues as $index => $value) {
					$this->selectedValues[$index] = Security::encrypt($value);
				}
			} else {
				$this->selectedValues = Security::encrypt($this->selectedValues);
			}
		}
	}

	public function getFilters()
	{
		$data = Request::data();
		if (empty($data)) {
			throw new \Exception('Error: empty filter data!');
		}
		return $data['filters'] ?? [];
	}
}