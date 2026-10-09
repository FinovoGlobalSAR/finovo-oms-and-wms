<?php

interface ConnectorInterface
{
    public function testConnection(): array;
    public function fetchProducts(): array;
    public function fetchOrders(): array;
    public function updateOrderStatus(string $externalOrderId, string $status): array;
    public function updateInventory(string $externalProductId, int $quantity): array;
    public function getHealth(): string;
}