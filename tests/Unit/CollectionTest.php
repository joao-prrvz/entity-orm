<?php

use EntityORM\Collection;
use EntityORM\Tests\DB;
use EntityORM\Tests\Models\Pet;
use EntityORM\Tests\Models\User;
use PHPUnit\Framework\Attributes\DataProviderClosure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function PHPSTORM_META\type;

class CollectionTest extends TestCase
{
    private static ?DB $_ctx = null;
    

    #[Test]
    #[DataProviderClosure(static function() {
        return [
            [User::class, CollectionTest::getDB()->users],
            [Pet::class, CollectionTest::getDB()->pets],
        ];
    })]
    public function select_all(string $class, Collection $col)
    {
        $this->assertContainsOnlyInstancesOf($class, $col->items);
    }

    #[Test]
    public function select_column()
    {
        $email = static::getDB()->users
            ->where(fn($u) => $u->id == 1)
            ->select(fn($u) => $u->email)[0];
        $this->assertEquals("alice@example.com", $email);
    }

    #[Test]
    public function select_column_unparsable()
    {
        $email = static::getDB()->users
            ->where(fn($u) => $u->id == 1)
            ->select(fn($u) => $this->unparsableSelector($u))[0];
        $this->assertEquals("alice@example.com", $email);
    }

    private function unparsableSelector(User $user)
    {
        return $user->email;
    }

    #[Test]
    public function where()
    {
        $user = static::getDB()->users
            ->where(fn($u) => $u->id == 1)->first();
        $this->assertEquals(1, $user->id);
    }

    #[Test]
    public function where_multiple()
    {
        $users = static::getDB()->users
            ->where(fn($u) => $u->id == 1 || $u->id == 2);
        $this->assertEquals(2, $users->length);

        foreach ($users->items as $user)
            $this->assertContains($user->id, [1, 2]);
    }

    #[Test]
    public function add()
    {
        $user = new User("new@gmail.com", "new");
        $db = static::getDB();
        $db->users->add($user);
        $userId = $db->lastInsertId;
        $user = $db->users
            ->where(fn($u) => $u->id === $userId)->first();
        $this->assertNotNull($user);
    }

    #[Test]
    public function remove()
    {
        $db = static::getDB();
        $user = $db->users->last();
        $db->users->remove($user);
        $user = $db->users
            ->where(fn($u) => $u->id == $user->id)->first();
        $this->assertNull($user);
    }

    #[Test]
    public function clear()
    {
        $db = static::getDB();
        $db->users->clear();
        $this->assertEquals(0, $db->users->length);
    }
    

    private static function getDB()
    {
        if (static::$_ctx === null)
            static::$_ctx = new DB();
        return static::$_ctx;
    }
}