<?php
namespace EntityORM\Queries;

class DeleteQuery implements IQuery
{
    public string $table;

    public string $sql {
        get => "DELETE FROM `$this->table`";
    }

    public function __construct(string $table)
    {
        $this->table = $table;
    }
}