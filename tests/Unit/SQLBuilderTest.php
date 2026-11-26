<?php
namespace EntityORM\Tests;

use EntityORM\SQLBuilder;
use EntityORM\Tests\Models\User;
use EntityORM\Tests\Models\UserType;
use PHPUnit\Framework\Attributes\DataProviderClosure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SQLBuilderTest extends TestCase
{
    #[Test]
    #[DataProviderClosure(static function() {
        return [
            [User::class, "User"]
        ];
    })]
    public function table(string $class, string $expected)
    {
        $builder = new SQLBuilder($class);
        $this->assertEquals($expected, $builder->table);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        return [
            [
                User::class, [
                    "id" => "id",
                    "name" => "name",
                    "email" => "email",
                    "type" => "type",
                    "createdAt" => "created_at"
                ]
            ]
        ];
    })]
    public function columns(string $class, array $expected)
    {
        $builder = new SQLBuilder($class);
        $this->assertEquals($expected, $builder->columns);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        return [
            [
                User::class, [
                    "name" => "name",
                    "email" => "email",
                ]
            ]
        ];
    })]
    public function insert_columns(string $class, array $expected)
    {
        $builder = new SQLBuilder($class);
        $this->assertEquals($expected, $builder->insertColumns);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        return [
            [
                User::class, [
                    "id" => "id",
                ]
            ]
        ];
    })]
    public function primary_keys(string $class, array $expected)
    {
        $builder = new SQLBuilder($class);
        $this->assertEquals($expected, $builder->primaryKeys);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        return [
            [
                User::class,
                fn(User $u) => $u->id,
                "SELECT `User`.`id` FROM `User`" 
            ]
        ];
    })]
    public function select_query(string $class, callable $selector, string $expected)
    {
        $builder = new SQLBuilder($class);
        $builder->select($selector);
        $this->assertEquals($expected, $builder->sql);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        return [
            [
                User::class, 
                fn(User $u) => $u->id == 1, 
                SQLBuilderTest::noBreakLines(
                    "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`type`, `User`.`created_at` 
                    FROM `User`
                    WHERE `User`.`id` = ?" 
                ),
            ],
            [
                User::class, 
                fn(User $u) => $u->id == 1 && $u->email == "email@example.com", 
                SQLBuilderTest::noBreakLines(
                    "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`type`, `User`.`created_at` 
                    FROM `User`
                    WHERE (`User`.`id` = ? AND `User`.`email` = ?)"
                ),
            ],
            [
                User::class, 
                fn(User $u) => $u->id == 1 && ($u->email == "email@example.com" || $u->email == "email@example2.com"), 
                SQLBuilderTest::noBreakLines(
                    "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`type`, `User`.`created_at` 
                    FROM `User`
                    WHERE (`User`.`id` = ? AND (`User`.`email` = ? OR `User`.`email` = ?))"
                ),
            ],
            [
                User::class, 
                fn(User $u) => $u->id == 1 && $u->email == "email@example.com" || $u->email == "email@example2.com", 
                SQLBuilderTest::noBreakLines(
                    "SELECT `User`.`id`, `User`.`email`, `User`.`name`, `User`.`type`, `User`.`created_at` 
                    FROM `User`
                    WHERE ((`User`.`id` = ? AND `User`.`email` = ?) OR `User`.`email` = ?)"
                ),
            ]
        ];
    })]
    public function where_query(string $class, callable $predicate, string $expected)
    {
        $builder = new SQLBuilder($class);
        $builder->where($predicate);
        $this->assertEquals($expected, $builder->sql);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        return [
            [
                User::class, 
                fn(User $u) => $u->id == 1,
                [1]
            ],
            [
                User::class, 
                fn(User $u) => $u->id == 1 && $u->email == "email@example.com",
                [1, "email@example.com"]
            ]
        ];
    })]
    public function where_params(string $class, callable $predicate, array $expected)
    {
        $builder = new SQLBuilder($class);
        $builder->where($predicate);
        $this->assertEquals($expected, $builder->params);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        return [
            [
                User::class, 
                [new User("email@example.com", "example_user")], 
                "INSERT INTO `User` (`email`, `name`) VALUES (?, ?)" 
            ],
            [
                User::class, 
                [
                    new User("email@example2.com", "example2_user"), 
                    new User("email@example3.com", "example3_user"), 
                ],
                "INSERT INTO `User` (`email`, `name`) VALUES (?, ?), (?, ?)" 
            ],
        ];
    })]
    public function insert_query(string $class, array $objects, string $expected)
    {
        $builder = new SQLBuilder($class);
        $builder->insert(...$objects);
        $this->assertEquals($expected, $builder->sql);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        $user = new User("email@example.com", "example_user"); 
        return [
            [
                User::class, 
                $user, 
                [$user->email, $user->name] 
            ]
        ];
    })]
    public function insert_params(string $class, object $object, array $expected)
    {
        $builder = new SQLBuilder($class);
        $builder->insert($object);
        $this->assertEquals($expected, $builder->params);
    }


    #[Test]
    #[DataProviderClosure(static function() {
        $user = new User("email@example.com", "example_user");
        $user->id = 1;
        $user2 = new User("email@example2.com", "example3_user");
        $user2->id = 2;
        return [
            [
                User::class, 
                [$user], 
                "DELETE FROM `User` WHERE (`User`.`id` = ?)"
            ],

            [
                User::class, 
                [$user, $user2], 
                "DELETE FROM `User` WHERE (`User`.`id` = ?) OR (`User`.`id` = ?)"
            ]
        ];
    })]
    public function delete_query(string $class, array $objects, string $expected)
    {
        $builder = new SQLBuilder($class);
        $builder->delete(...$objects);
        $this->assertEquals($expected, $builder->sql);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        $user = new User("email@example.com", "example_user");
        $user->id = 1;
        $user2 = new User("email@example2.com", "example3_user");
        $user2->id = 2;
        return [
            [
                User::class, 
                [$user], 
                [$user->id]
            ],
            [
                User::class, 
                [$user, $user2], 
                [$user->id, $user2->id]
            ]
        ];
    })]
    public function delete_params(string $class, array $objects, array $expected)
    {
        $builder = new SQLBuilder($class);
        $builder->delete(...$objects);
        $this->assertEquals($expected, $builder->params);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        $user = new User("email@example.com", "example_user");
        $user->id = 1;
        $user->type = UserType::MEMBER;
        return [
            [
                User::class, 
                $user, 
                "UPDATE `User` SET `name` = ?, `type` = ? WHERE (`User`.`id` = ?)"
            ]
        ];
    })]
    public function update_query(string $class, object $objects, string $expected)
    {
        $builder = new SQLBuilder($class);
        $builder->update($objects);
        $this->assertEquals($expected, $builder->sql);
    }

    #[Test]
    #[DataProviderClosure(static function() {
        $user = new User("email@example.com", "example_user");
        $user->name = "NEW";
        $user->type = UserType::MEMBER;
        $user->id = 1;
        return [
            [
                User::class, 
                $user, 
                [$user->name, $user->type, $user->id]
            ]
        ];
    })]
    public function update_params(string $class, object $objects, array $expected)
    {
        $builder = new SQLBuilder($class);
        $builder->update($objects);
        $this->assertEquals($expected, $builder->params);
    }

    private static function noBreakLines(string $value): string
    {
        return preg_replace('/\s+/', ' ', $value);
    }
}