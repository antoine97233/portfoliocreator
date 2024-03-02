<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SkillWithCountDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly int $count
    ) {
    }
}
