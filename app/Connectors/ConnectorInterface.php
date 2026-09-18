<?php
/**
 * Har platform (Shopify, WooCommerce, Custom Bridge, Generic API) ye
 * interface implement karta hai. Baaki business logic (controllers)
 * ko kabhi pata nahi hota ke background mein kaunsa platform hai.
 */
interface ConnectorInterface
{
    public function testConnection(): array;
    public function fetchProducts(): array;
    public function fetchOrders(): array;
    public function updateOrderStatus(string $externalOrderId, string $status): array;
    public function updateInventory(string $externalProductId, int $quantity): array;
    public function getHealth(): string;
}