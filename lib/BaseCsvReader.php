<?php

class BaseCsvReader 
{
	protected $file = null;
	protected $columns = array();

	public function __construct($filename) {
		$this->file = fopen($filename, "r");
		$this->columns = fgetcsv($this->file);
		if(!$this->file) {
			return false;
		}
	}

	public function GetColumns()
	{
		return $this->columns;
	}

	public function Read() 
	{
		if($this->file) {
			$data = array();
			if($row = fgetcsv($this->file)) {
				foreach ($this->columns as $key => $value) {
					$data[$value] = $row[$key];
				}
				return $data;
			}
		}
		return false;
	}

	public function SetColumns($map) {
		if($map) {
			foreach($map as $key => $value) {
				$this->columns[$key] = $value;
			}
		}
	}

	public function __destruct() {
		if($file) {
			fclose($file);
		}
	}
}

?>