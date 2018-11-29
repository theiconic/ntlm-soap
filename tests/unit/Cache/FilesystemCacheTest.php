<?php

namespace Test\Unit\TheIconic\NtlmSoap\Cache;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use TheIconic\NtlmSoap\Cache\FilesystemCache;

class FilesystemCacheTest extends TestCase
{
    public function testGetItem()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->any())->method('exists')->willReturn(true);

        $cache = new FilesystemCache($filesystem, '');
        $this->assertStringEndsWith('foo', $cache->get('foo'));
    }

    public function testGetInexistentItem()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->any())->method('exists')->willReturn(false);

        $cache = new FilesystemCache($filesystem, '');
        $this->assertNull($cache->get('foo'));
    }

    public function testGetExpiredItem()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->any())->method('exists')->willReturn(true);

        $cache = new FilesystemCache($filesystem, __DIR__, -1);
        $this->assertNull($cache->get(''));
    }

    public function testHasItem()
    {
        $filesystem = $this->createMock(Filesystem::class);

        $filesystem->expects($this->once())->method('exists')->willReturn(true);

        $cache = new FilesystemCache($filesystem, '');
        $this->assertTrue($cache->has('foo'));
    }

    public function testPutItem()
    {
        $filesystem = $this->createMock(Filesystem::class);

        $filesystem->expects($this->once())->method('dumpFile');

        $cache = new FilesystemCache($filesystem, '');
        $this->assertStringEndsWith('foo', $cache->put('foo', ''));
    }
}
