<?php
/**
 * Platform ke product / variant se stock ki quantity nikalna.
 *
 * null = platform is product ka stock GINTA hi nahi (jaise WooCommerce mein "Manage stock" off,
 * Shopify mein "Track quantity" off, BigCommerce mein inventory tracking "none").
 * Aise product ki koi quantity hoti hi nahi — pehle hum use 0 maan lete the aur product
 * "out of stock" dikhta tha. Ab null aata hai: Finovo ka stock nahi chhedte aur user ko batate hain.
 */
class PlatformStock
{
    /** Shopify variant: inventory_management = "shopify" → gina jata hai, null → nahi */
    public static function shopifyVariant(array $variant): ?int
    {
        if (array_key_exists('inventory_management', $variant) && $variant['inventory_management'] === null) {
            return null;
        }
        return (int) ($variant['inventory_quantity'] ?? 0);
    }

    /**
     * WooCommerce product / variation:
     *   manage_stock = true (ya variation pe "parent") → stock_quantity
     *   manage_stock = false → quantity nahi; sirf "outofstock" ho to 0, warna null
     */
    public static function wooCommerce(array $item): ?int
    {
        $managed = $item['manage_stock'] ?? false;
        if (($managed === true || $managed === 'parent') && is_numeric($item['stock_quantity'] ?? null)) {
            return (int) $item['stock_quantity'];
        }
        if (($item['stock_status'] ?? '') === 'outofstock') {
            return 0;
        }
        return null;
    }

    /** BigCommerce product: inventory_tracking "none" → gina nahi jata */
    public static function bigCommerce(array $product): ?int
    {
        if (($product['inventory_tracking'] ?? 'product') === 'none') {
            return null;
        }
        return (int) ($product['inventory_level'] ?? 0);
    }

    /**
     * Wix product: quantity sirf tab hoti hai jab "Track inventory" on ho.
     * Warna sirf "in stock / out of stock" — out of stock ho to 0, warna null.
     */
    public static function wix(array $product): ?int
    {
        $qty = $product['inventory']['availableQuantity'] ?? $product['stock']['quantity'] ?? null;
        if (is_numeric($qty)) {
            return (int) $qty;
        }
        $status = strtoupper((string) ($product['inventory']['availabilityStatus'] ?? ''));
        $inStock = $product['stock']['inStock'] ?? null;
        if ($status === 'OUT_OF_STOCK' || $inStock === false) {
            return 0;
        }
        return null;
    }
}