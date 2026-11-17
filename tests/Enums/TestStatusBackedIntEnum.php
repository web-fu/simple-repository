<?php

namespace WebFu\SimpleRepository\Tests\Enums;

enum TestStatusBackedIntEnum: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;
}