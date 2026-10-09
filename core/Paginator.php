<?php

class Paginator
{
    public const PER_PAGE = 25;

    public static function make(array $items, int $perPage = self::PER_PAGE): array
    {
        $items = array_values($items);
        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($items, $offset, $perPage),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'perPage' => $perPage,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($total, $offset + $perPage),
        ];
    }

    /** Isi page ka URL, sirf ?page= badla hua (search / filters waise hi) */
    public static function url(int $page): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $query = $_GET;
        // Ek dafa ke messages (synced=, deleted=...) agle page pe dobara na dikhen
        foreach (['synced', 'stock_synced', 'untracked', 'deleted', 'updated', 'created', 'imported', 'exported', 'pushed', 'error', 'stock_updated', 'stock_warning', 'queued', 'push_note'] as $oneTime) {
            unset($query[$oneTime]);
        }
        $query['page'] = $page;
        return $path . '?' . http_build_query($query);
    }

    /** Page numbers: 1 … 4 5 [6] 7 8 … 20  (null = "…") */
    public static function window(int $page, int $pages, int $around = 2): array
    {
        $out = [];
        $last = 0;
        for ($i = 1; $i <= $pages; $i++) {
            if ($i === 1 || $i === $pages || abs($i - $page) <= $around) {
                if ($last && $i - $last > 1) {
                    $out[] = null;
                }
                $out[] = $i;
                $last = $i;
            }
        }
        return $out;
    }
}