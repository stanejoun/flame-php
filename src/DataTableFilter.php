<?php

namespace Stanejoun\FlamePHP;

class DataTableFilter
{
	const BUTTON_TYPE = 'button';
	const LIST_TYPE = 'list';
	const DATE_TYPE = 'date';
	const PERIOD_TYPE = 'period';

	public string $column = '';
	public bool $isEncryptedField = false;
	public mixed $selectedValues = null;
	public string $dateFrom = '';
	public string $dateTo = '';
	public string $period = '';
	public string $type = '';
	public bool $isSerializedData = false;
}