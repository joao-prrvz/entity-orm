<?php
namespace EntityORM\Components;

class SQLColumn
{
    public string $name;
    public ?string $table;

    public string $sql {
        get => $this->table === null ? $this->name : "`$this->table`.`$this->name`";
    }

    public function __construct(string $name, ?string $table = null)
    {
        $this->name = $name;
        $this->table = $table;
    }

}