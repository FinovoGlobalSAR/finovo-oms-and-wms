<?php
require_once __DIR__ . '/../Models/Product.php';

/**
 * Orders sync karte waqt ye check karta hai ke order ka product Finovo mein
 * pehle se exist karta hai ya nahi. Agar naya/unknown product hai, to us
 * order ko skip kar dete hain — naya product khud nahi banate. Isse
 * "sirf jo products hum manage karte hain unhi ke orders aayen" guarantee
 * hoti hai, platform pe jo bhi ho.
 */
class ProductAvailabilityGuard
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    /**
     * @return array{allowed: bool, product_id: ?int}
     */
    public function check(int $storeId, string $productName): array
    {
        $existing = $this->productModel->findByName($storeId, $productName);

        if (!$existing) {
            return ['allowed' => false, 'product_id' => null];
        }

        return ['allowed' => true, 'product_id' => (int) $existing['id']];
    }
}