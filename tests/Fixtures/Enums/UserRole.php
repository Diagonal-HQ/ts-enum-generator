<?php

namespace Diagonal\TsEnumGenerator\Tests\Fixtures\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case MODERATOR = 'moderator';
    case GUEST = 'guest';
}
