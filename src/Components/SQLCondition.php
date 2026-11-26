<?php
namespace EntityORM\Components;

use Exception;

class SQLCondition
{
    public string $expression;
    public SQLOperator $operator;

    public function __construct(string $expression, SQLOperator $operator = SQLOperator::And)
    {
        $this->expression = $expression;
        $this->operator = $operator;
    }

    public static function group(array $conditions, SQLOperator $operator = SQLOperator::And): SQLCondition
    {
        if (count($conditions) < 1)
            throw new Exception("No conditions given");
        $result = "";
        foreach ($conditions as $index => $condition) {
            $result .= $condition->format($index > 0). " ";
        }
        $result = rtrim($result);
        return new SQLCondition("($result)", $operator);
    }

    public function format(bool $displayOperator)
    {
        $operator = $displayOperator ? $this->operator->value : "";
        return ltrim("$operator $this->expression");
    }
}