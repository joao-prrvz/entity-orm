<?php
namespace EntityORM\Tests\Models;

enum UserType: string
{
    case ADMIN = "admin";
    case MEMBER = "member";
}