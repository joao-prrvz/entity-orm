<?php
namespace EntityORM\Tests\Models;

use DateTime;
use EntityORM\Attributes as DB;

class User
{
    #[DB\PrimaryKey, DB\Block]
    public int $id;

    #[DB\Block(DB\Block::UPDATE)]
    public string $email;

    public string $name;

    #[DB\Block(DB\Block::INSERT)]
    public UserType $type;

    #[DB\Column("created_at"), DB\Block, DB\Date(DB\Date::DATETIME)]
    public DateTime $createdAt;

    public function __construct(string $email, string $name)
    {
        $this->email = $email;
        $this->name = $name;
    }
}