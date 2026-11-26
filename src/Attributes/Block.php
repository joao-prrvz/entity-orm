<?php
namespace EntityORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Block
{
    public const int INSERT = 0;
    public const int UPDATE = 1;
    public const int ALL = 2;

    /**
     * Undocumented variable
     *
     * @var int[]
     */
    public array $values; 

    public function __construct(int $value = Block::ALL, int ...$values)
    {
        $values[] = $value;
        $this->values = $values;
    }
}