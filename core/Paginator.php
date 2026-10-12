<?php

class Paginator
{
    public const PER_PAGE = 25;

    /** Har table mein kitne latest records dikhen */
    public const LATEST = 5;

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

    /**
     * Table mein sirf LATEST records (team lead: "thoda sa data latest display ke liye, baaki sirf database mein").
     *
     * $all      = poori list (bina search / filter ke)
     * $filtered = search / filter ke baad wali list
     * Pehle $all se sabse naye $limit records chunte hain, phir unhi mein se jo $filtered mein hain woh dikhte hain.
     * Is tarah search / filter se bhi purana record table mein nahi aata. Pages (Prev / Next) nahi hain.
     *
     * $sortById = true  → "latest" ka matlab sab se bara id (naya record)
     * $sortById = false → list jis tarteeb mein aayi wohi (jaise customers: jis ka order sab se naya)
     */
    public static function latest(array $filtered, array $all, int $limit = self::LATEST, bool $sortById = true): array
    {
        $all = array_values($all);
        $filtered = array_values($filtered);
        if ($sortById) {
            $byIdDesc = fn($a, $b) => (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
            usort($all, $byIdDesc);
            usort($filtered, $byIdDesc);
        }

        $latestIds = [];
        foreach (array_slice($all, 0, $limit) as $row) {
            $latestIds[(int) ($row['id'] ?? 0)] = true;
        }
        $items = array_values(array_filter($filtered, fn($row) => isset($latestIds[(int) ($row['id'] ?? 0)])));

        return [
            'items' => $items,
            'page' => 1,
            'pages' => 1,
            'total' => count($all),   // database mein kul kitne (sirf ginti dikhti hai, records nahi)
            'perPage' => $limit,
            'from' => $items ? 1 : 0,
            'to' => count($items),
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