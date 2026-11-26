<?php
namespace EntityORM;

use DateTime;
use EntityORM\Attributes\Date;
use EntityORM\Queries\DeleteQuery;
use EntityORM\Queries\SelectQuery;
use Exception;
use PDOStatement;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Undocumented class
 * 
 * @template T
 */
class Collection
{
    private DBContext $ctx;
    public SQLBuilder $builder;

    public int $length {
        get {
            $collection = clone $this;
            $collection->builder->count();
            $sttmt = $collection->run();
            return (int)$sttmt->fetch()["COUNT(*)"];
        }
    }

    /**
     * Undocumented variable
     *
     * @var T[]
     */
    public array $items {
        get {
            if ($this->_items !== null)
                return $this->_items;
            $sttmt = $this->run();
            $values = $sttmt->fetchAll();
            $query = $this->builder->query;
            if ($query instanceof SelectQuery && $query->selector !== null)
                return array_map(
                    fn($data) => ($query->selector)($this->instanciate($data)), 
                    $values
                );
            return array_map(fn($data) => $this->instanciate($data), $values);
        }
    }

    private ?array $_items = null;

    /**
     * Undocumented function
     *
     * @param class-string<T> $modelClass
     * @param DBContext $ctx
     */
    public function __construct(string $modelClass, DBContext $ctx)
    {
        $this->builder = new SQLBuilder($modelClass);
        $this->ctx = $ctx;
    }

    public function __clone()
    {
        $this->builder = clone $this->builder;
    }

    /**
     * Undocumented function
     *
     * @param T ...$items
     * @return void
     */
    public function add(mixed ...$items): void
    {
        $collection = clone $this;
        $collection->builder->insert(...$items);
        $collection->run();
    }

    /**
     * Undocumented function
     *
     * @param T ...$items
     * @return void
     */
    public function remove(mixed ...$items): void
    {
        $collection = clone $this;
        $collection->builder->delete(...$items);
        $collection->run();
    }

    public function clear(): void
    {
        $collection = clone $this;
        $collection->builder->query = new DeleteQuery($collection->builder->table);
        $collection->run();
    }
    
    /**
     * Undocumented function
     *
     * @param callable(T): bool $predicate
     * @return Collection<T>
     */
    public function where(callable $predicate): Collection
    {
        $collection = clone $this;
        $collection->builder->where($predicate);
        return $collection;
    }

    /**
     * Undocumented function
     *
     * @template TResult
     * @param callable(T): TResult $selector
     * @return TResult[]
     */
    public function select(callable $selector): array
    {
        $collection = clone $this;
        $result = $collection->builder->select($selector);
        if (!$result)
            $collection->_items = array_map($selector, $collection->items);
        return $collection->items;
    }

    /**
     * Undocumented function
     *
     * @param int $limit
     * @param int $offset
     * @return Collection<T>
     */
    public function take(int $limit, int $offset = 0): Collection
    {
        $collection = clone $this;
        $collection->builder->limit = $limit;
        $collection->builder->offset = $offset;
        return $collection;
    }

    /**
     * Undocumented function
     *
     * @return ?T
     */
    public function first(): mixed
    {
        return array_first($this->take(1)->items);
    }

    /**
     * Undocumented function
     *
     * @return ?T
     */
    public function last(): mixed
    {
        return array_first($this->take(1, $this->length - 1)->items);
    }

    private function instanciate(array $data): mixed
    {
        $refModel = $this->builder->refModel;
        $obj = $refModel->newInstanceWithoutConstructor();
        foreach ($data as $column => $value) {
            $propName = array_search($column, $this->builder->columns);
            $refProp = $refModel->getProperty($propName);
            $refProp->setValue($obj, $this->getConvertedValue($refProp, $value));
        }
        return $obj;
    }

    private function getConvertedValue(ReflectionProperty $refProp, mixed $value): mixed
    {
        $refType = $refProp->getType();
        if (!($refType instanceof ReflectionNamedType))
            throw new Exception("Union types not supported");
        $typeName = $refType->getName();
        if (enum_exists($typeName))
            return $typeName::from($value);
        if ($typeName === DateTime::class) {
            $refDate = $refProp->getAttributes(Date::class)[0] ?? null;
            if ($refDate === null)
                throw new Exception("Use Date Attribute to specify the format of your DateTime");
            return DateTime::createFromFormat($refDate->newInstance()->format, $value);
        }
        return $value;
    }

    private function run(): PDOStatement
    {
        return $this->ctx->execute($this->builder->sql, $this->builder->params);
    }
}