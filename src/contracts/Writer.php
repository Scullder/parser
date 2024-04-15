<?php

namespace Src\Contracts;

interface Writer
{
    public function write(string $path, array $data): bool;
}