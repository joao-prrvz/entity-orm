<?php
namespace EntityORM\Queries;

use Closure;
use EntityORM\Components\SQLColumn;

class SelectQuery implements IQuery
{
    public string $table;
    /**
     * Undocumented variable
     *
     * @var SQLColumn[]
     */
    public array $columns;
    public ?int $limit = null; 
    public int $offset = 0;
    public ?Closure $selector;
    
    public string $sql {
        get {
            $columns = $this->formatColumns();
            $table = $this->table;
            $sql = "SELECT $columns FROM `$table`";
            return rtrim($sql);
        }
    }

    /**
     * Undocumented function
     *
     * @param string $table
     * @param SQLColumn[] $columns
     * @param callable|null $selector
     */
    public function __construct(string $table, array $columns, ?callable $selector = null)
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->selector = $selector;
    }

    public function __clone()
    {
        if ($this->selector !== null)
            $this->selector = clone $this->selector;
    }

    private function formatColumns(): string
    {
        return implode(", ", array_map(fn($c) => $c->sql, $this->columns));
    }
}