<?php

namespace Tests\Utils;

use App\Utils\Slugger;
use PHPUnit\Framework\TestCase;

class SluggerTest extends TestCase
{
    public function testSlugifyUtf8RemovesVietnameseAccents()
    {
        $slugger = new Slugger();

        $this->assertSame(
            'xay-nha-tron-goi-tphcm',
            $slugger->slugifyUtf8('Xây nhà trọn gói TPHCM')
        );
    }

    public function testSlugifyLowercasesAndReplacesWhitespace()
    {
        $slugger = new Slugger();

        $this->assertSame('hello-world', $slugger->slugify('  Hello World  '));
    }
}
