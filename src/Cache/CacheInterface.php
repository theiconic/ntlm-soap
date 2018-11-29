<?php

namespace TheIconic\NtlmSoap\Cache;

interface CacheInterface
{
    public function get(string $item): ?string;
    public function has(string $item): bool;
    public function put(string $item, string $contents): string;
}
