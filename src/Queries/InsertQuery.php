<?php
namespace EntityORM\Queries;

use Exception;

class InsertQuery implements IQuery
{
    public string $table;
    public array $columns;
    
    public int $count {
        get => $this->_count;
        set {
            if ($value < 1)
                throw new Exception("Count must be bigger than 0");
            $this->_count = $value;
        }
    }

    public string $sql {
        get {
            $columns = $this->getFormatedColumns();
            $table = $this->table;
            $values = $this->getFormatedValues();
            $sql = "INSERT INTO `$table` ($columns) VALUES $values";
            return rtrim($sql);
        }
    }

    private int $_count;

    public function __construct(string $table, array $columns, int $count)
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->count = $count;
    }

    private function getFormatedColumns(): string
    {
        return "`".implode("`, `", $this->columns)."`";
    }

    private function getFormatedValues(): string
    {
        $value = implode(", ", array_fill(0, count($this->columns), "?"));
        return implode(", ", array_fill(0, $this->count, "($value)"));
    }
}