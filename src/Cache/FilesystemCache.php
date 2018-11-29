<?php

namespace TheIconic\NtlmSoap\Cache;

use DateTime;
use Symfony\Component\Filesystem\Filesystem;

class FilesystemCache implements CacheInterface
{
    private $filesystem;
    private $cacheDir;
    private $defaultTtl;

    public function __construct(Filesystem $filesystem, string $cacheDir, int $defaultTtl = 0)
    {
        $this->filesystem = $filesystem;
        $this->cacheDir = $cacheDir;
        $this->defaultTtl = $defaultTtl;
    }

    public function get(string $item): ?string
    {
        if (!$this->has($item)) {
            return null;
        }

        if ($this->defaultTtl && $this->isExpired($item, $this->defaultTtl)) {
            return null;
        }

        return $this->getFullPath($item);
    }

    public function has(string $item): bool
    {
        return $this->filesystem->exists($this->getFullPath($item));
    }

    public function put(string $item, string $contents): string
    {
        $path = $this->getFullPath($item);

        $this->filesystem->dumpFile($path, $contents);

        return $path;
    }

    private function isExpired(string $item, int $ttl): bool
    {
        $modifiedAt = new DateTime(date('c', filemtime($this->getFullPath($item))));
        $validUntil = (clone $modifiedAt)->modify(sprintf('+%d seconds', $ttl));
        $now = new DateTime('now', $modifiedAt->getTimezone());

        return $now >= $validUntil;
    }

    private function getFullPath(string $fileName): string
    {
        return sprintf('%s/%s', $this->cacheDir, $fileName);
    }
}
