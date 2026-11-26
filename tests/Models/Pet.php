<?php
namespace EntityORM\Tests\Models;
use EntityORM\Attributes as DB;

class Pet
{
    #[DB\PrimaryKey, DB\Block]
    public int $id;
    public string $name;
    #[DB\Column("user_id")]
    public int $userId;
}