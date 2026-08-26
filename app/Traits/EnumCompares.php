<?php

declare(strict_types=1);

namespace App\Traits;

trait EnumCompares
{
    public function equals(self $other): bool
    {
        return $this === $other;
    }

    public function notEquals(self $other): bool
    {
        return !$this->equals($other);
    }

    /**
     * @param  iterable<self>  $list
     */
    public function in(iterable $list): bool
    {
        foreach ($list as $case) {
            if ($this === $case) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  iterable<self>  $list
     */
    public function notIn(iterable $list): bool
    {
        return !$this->in($list);
    }
}
