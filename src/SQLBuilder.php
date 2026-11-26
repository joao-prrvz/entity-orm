<?php
namespace EntityORM;

use Closure;
use DateTime;
use EntityORM\Attributes as DB;
use EntityORM\Components\SQLColumn;
use EntityORM\Components\SQLCondition;
use EntityORM\Components\SQLOperator;
use EntityORM\Parsers\SelectParser;
use EntityORM\Parsers\WhereParser;
use EntityORM\Queries\DeleteQuery;
use EntityORM\Queries\InsertQuery;
use EntityORM\Queries\IQuery;
use EntityORM\Queries\SelectQuery;
use EntityORM\Queries\UpdateQuery;
use Exception;
use ReflectionClass;
use ReflectionProperty;

/**
 * Undocumented class
 * 
 * @template T
 */
class SQLBuilder
{
    public ReflectionClass $refModel;
    public array $params = [];
    public IQuery $query;

    /**
     * Undocumented variable
     *
     * @var SQLCondition[]
     */
    public array $conditions = [];

    public ?int $limit = null; 
    public int $offset = 0;

    public string $table {
        get => array_last(explode("\\", $this->refModel->name));
    }

    /**
     * Dictionary with property names as keys and columns as values
     *
     * @var array<string, string>
     */
    public array $columns {
        get => $this->getColumnsNotBlocked();
    }

    /**
     * Undocumented variable
     *
     * @var array<string, string>
     */
    public array $insertColumns {
        get => $this->getColumnsNotBlocked(DB\Block::INSERT);
    }

    /**
     * Undocumented variable
     *
     * @var array<string, string>
     */
    public array $updateColumns {
        get => $this->getColumnsNotBlocked(DB\Block::UPDATE);
    }

    /**
     * Undocumented variable
     *
     * @var array<string, string>
     */
    public array $primaryKeys {
        get {
            return array_filter($this->columns, function($propName) {
                $refProp = $this->refModel->getProperty($propName);
                return array_first($refProp->getAttributes(DB\PrimaryKey::class)) != null;
            }, 2);
        }
    }

    public string $sql {
        get => $this->query->sql. $this->getFormatedConditions();
    }

    public function __construct(string $modelClass)
    {
        $this->refModel = new ReflectionClass($modelClass);
        $this->query = new SelectQuery(
            $this->table, 
            array_map(fn($c) => new SQLColumn($c, $this->table), $this->columns)
        );
    }

    public function __clone()
    {
        $this->conditions = array_map(fn($c) => clone $c, $this->conditions);
        $this->query = clone $this->query;
    }

    /**
     * Undocumented function
     *
     * @param callable<T, bool> $selector
     * @return bool
     */
    public function select(callable $selector): bool
    {
        $parser = new SelectParser($this->table, $this->columns);
        
        $result = $parser->parse($selector);
        if (!$result)
            return false;
        if ($this->query instanceof SelectQuery) {
            $this->query->selector = Closure::fromCallable($selector);
            $this->query->columns = array_map(fn($c) => new SQLColumn($c, $this->table), $parser->selectColumnNames);
            return true;
        }

        $this->query = new SelectQuery($this->table, 
            array_map(fn($c) => new SQLColumn($c, $this->table), $parser->selectColumnNames), 
            $selector
        );
        return true;
    }

    /**
     * Undocumented function
     *
     * @template TResult
     * @param callable<T, TResult> $predicate
     * @return bool
     */
    public function where(callable $predicate): bool
    {
        $parser = new WhereParser($this->table, $this->columns);
        $result = $parser->parse($predicate);
        if (!$result)
            return false;
        $this->params = $parser->params;
        $this->conditions = $parser->conditions;
        return true;
    }

    public function insert(object ...$objects)
    {
        $this->query = new InsertQuery($this->table, $this->insertColumns, count($objects));
        foreach ($objects as $object) {
            foreach ($this->insertColumns as $propName => $column) {
                $refProp = $this->refModel->getProperty($propName);
                $this->params[] = $this->getPropValue($refProp, $object);
            }
        }
    }

    public function delete(object ...$objects)
    {
        $this->query = new DeleteQuery($this->table);
        foreach ($objects as $object) {
            $conditions = [];
            foreach ($this->primaryKeys as $propName => $column) {
                $conditions[] = new SQLCondition("`$this->table`.`$column` = ?");
                $this->params[] = $this->refModel->getProperty($propName)->getValue($object);
            }
            $this->conditions[] = SQLCondition::group($conditions, SQLOperator::Or);
        }
    }

    public function update(object $object)
    {
        $this->query = new UpdateQuery($this->table, $this->updateColumns);
        foreach ($this->updateColumns as $propName => $column) {
            $this->params[] = $this->refModel->getProperty($propName)->getValue($object);
        }
        $conditions = [];
        foreach ($this->primaryKeys as $propName => $column) {
            $conditions[] = new SQLCondition("`$this->table`.`$column` = ?");
            $this->params[] = $this->refModel->getProperty($propName)->getValue($object);
        }
        $this->conditions[] = SQLCondition::group($conditions, SQLOperator::Or);
    }

    public function count()
    {
        $this->query = new SelectQuery($this->table, [new SQLColumn("COUNT(*)")]);
    }

    private function getFormatedColumns(): array
    {
        return array_map(fn(string $c) => "`{$this->table}`.`{$c}`", $this->columns);
    }

    private function getPropColumnName(ReflectionProperty $refProp)
    {
        $refAttr = array_first($refProp->getAttributes(DB\Column::class));
        if ($refAttr === null)
            return $refProp->name;
        return $refAttr->newInstance()->name;
    }

    private function getPropValue(ReflectionProperty $refProp, object $obj)
    {
        $value = $refProp->getValue($obj);
        $typeName = get_debug_type($value);
        if (enum_exists($typeName))
            return $value->value;
        if ($typeName === DateTime::class) {
            $refDate = $refProp->getAttributes(DB\Date::class)[0] ?? null;
            if ($refDate === null)
                throw new Exception("Use a Date Attribute to convert the SQL data into PHP DateTime class");
            return $obj->format($refDate->newInstance()->format);
        }
        return $value;
    }

    private function getColumnsNotBlocked(?int $blockValue = null)
    {
        $columns = [];
        $refProps = $this->refModel->getProperties();
        $columns = [];

        foreach ($refProps as $refProp) {
            if (!$this->isPropBlocked($refProp, $blockValue))
                $columns[$refProp->name] = $this->getPropColumnName($refProp);
        }

        return $columns;
    }
    
    private function isPropBlocked(ReflectionProperty $refProp, ?int $blockValue)
    {
        if ($blockValue === null)
            return false;

        $refAttr = array_first($refProp->getAttributes(DB\Ignore::class));
        if ($refAttr !== null)
            return true;

        $refAttr = array_first($refProp->getAttributes(DB\Block::class));
        if ($refAttr === null)
            return false;

        $values = $refAttr->newInstance()->values;
        return in_array(DB\Block::ALL, $values) || in_array($blockValue, $values);
    }

    private function getFormatedLimitAndOffset(): string
    {
        if ($this->limit === null)
            return "";
        $limit = $this->limit;
        $offset = $this->offset;
        return "LIMIT $limit OFFSET $offset";
    }

    private function getFormatedConditions(): string
    {
        if (count($this->conditions) < 1 && $this->limit === null && $this->offset === 0)
            return "";
        else if (count($this->conditions) < 1 && ($this->limit !== null || $this->offset !== 0))
            return rtrim(" ".$this->getFormatedLimitAndOffset());
        $result = " WHERE ";
        foreach ($this->conditions as $index => $condition) {
            $result .= $condition->format($index > 0). " ";
        }
        return rtrim($result. $this->getFormatedLimitAndOffset());
    }
}