<?php

namespace Tests\Feature\Services;

use App\Services\CacheControl;
use Tests\TestCase;

class CacheControlTest extends TestCase
{
    public function testCacheControl()
    {
        $cc = new CacheControl(null);

        $this->assertNull($cc->maxAge());
        $this->assertNull($cc->maxStale());
        $this->assertNull($cc->minFresh());
        $this->assertFalse($cc->noCache());
        $this->assertFalse($cc->noStore());
        $this->assertFalse($cc->onlyIfCached());

        $cc = new CacheControl('max-age=86400, no-cache, no-store');

        $this->assertEquals(86400, $cc->maxAge());
        $this->assertTrue($cc->noCache());
        $this->assertTrue($cc->noStore());
        $this->assertFalse($cc->onlyIfCached());
        $this->assertNull($cc->maxStale());
        $this->assertNull($cc->minFresh());

        $cc = new CacheControl('max-age=86400, max-stale');

        $this->assertEquals(86400, $cc->maxAge());
        $this->assertTrue($cc->maxStale());
        $this->assertFalse($cc->noCache());
        $this->assertFalse($cc->noStore());
        $this->assertNull($cc->maxAgeWithStale());
        $this->assertEquals(86400, $cc->maxAgeWithFresh());

        $cc = new CacheControl('max-age=86400, max-stale=100, min-fresh=100');

        $this->assertEquals(86400, $cc->maxAge());
        $this->assertEquals(100, $cc->maxStale());
        $this->assertFalse($cc->noCache());
        $this->assertFalse($cc->noStore());
        $this->assertEquals(86500, $cc->maxAgeWithStale());
        $this->assertEquals(86300, $cc->maxAgeWithFresh());
    }
}
