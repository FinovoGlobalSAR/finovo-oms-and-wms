<?php
require_once __DIR__ . '/../core/Paginator.php';

use PHPUnit\Framework\TestCase;

/** Lambi list → pages (25 per page), aur page links mein filters waise hi rehte hain */
class PaginatorTest extends TestCase
{
    protected function tearDown(): void
    {
        $_GET = [];
        unset($_SERVER['REQUEST_URI']);
    }

    public function testSlicesTheRightPage(): void
    {
        $_GET = ['page' => '2'];
        $p = Paginator::make(range(1, 60), 25);
        $this->assertSame(range(26, 50), $p['items']);
        $this->assertSame(3, $p['pages']);
        $this->assertSame(26, $p['from']);
        $this->assertSame(50, $p['to']);
        $this->assertSame(60, $p['total']);
    }

    public function testBadPageNumbersAreClamped(): void
    {
        $_GET = ['page' => '99'];
        $p = Paginator::make(range(1, 30), 25);
        $this->assertSame(2, $p['page']);
        $this->assertSame([26, 27, 28, 29, 30], $p['items']);

        $_GET = ['page' => '-3'];
        $this->assertSame(1, Paginator::make(range(1, 30), 25)['page']);

        $_GET = [];
        $empty = Paginator::make([], 25);
        $this->assertSame(1, $empty['pages']);
        $this->assertSame(0, $empty['from']);
    }

    public function testUrlKeepsFiltersAndDropsOneTimeMessages(): void
    {
        $_SERVER['REQUEST_URI'] = '/customers?q=ali&store=3&synced=1&page=1';
        $_GET = ['q' => 'ali', 'store' => '3', 'synced' => '1', 'page' => '1'];
        $url = Paginator::url(4);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('/customers', parse_url($url, PHP_URL_PATH));
        $this->assertSame(['q' => 'ali', 'store' => '3', 'page' => '4'], $q);
    }

    public function testWindowShowsGaps(): void
    {
        $this->assertSame([1, null, 4, 5, 6, 7, 8, null, 20], Paginator::window(6, 20));
        $this->assertSame([1, 2, 3], Paginator::window(1, 3));
    }
}