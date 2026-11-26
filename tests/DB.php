<?php
namespace EntityORM\Tests;

use EntityORM\Collection;
use EntityORM\DBContext;
use EntityORM\Tests\Models\Pet;
use EntityORM\Tests\Models\User;

class DB extends DBContext
{
     /** @var Collection<User>*/
    public Collection $users;
    /** @var Collection<Pet>*/
    public Collection $pets;

    public function __construct()
    {
        if (file_exists(__DIR__ . "/test.db"))
            unlink(__DIR__ . "/test.db");

        parent::__construct("sqlite:" . __DIR__ . "/test.db"); // then connect (creates fresh file)

        $this->users = new Collection(User::class, $this);
        $this->pets  = new Collection(Pet::class, $this);

        $sql = file_get_contents(__DIR__ . "/seed.sql");
        $this->run($sql);
    }
}