<?php
namespace EntityORM\Queries;

class UpdateQuery implements IQuery
{
    public string $table;
    public array $columns;

    public function __construct(string $table, array $columns)
    {
        $this->table = $table;
        $this->columns = $columns;
    }

    public string $sql
    {
        get {
            $columns = $this->formatColumns();
            $table = $this->table;
            $sql = "UPDATE `$table` SET $columns";
            return rtrim($sql);
        }
    }

    private function formatColumns(): string
    {
        return "`".implode("` = ?, `", $this->columns)."` = ?";
    }
}