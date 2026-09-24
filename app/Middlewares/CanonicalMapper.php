<?php

class CanonicalMapper
{
    // Yeh Finovo ke apne, standard order statuses hain — sab platforms
    // ke alag-alag status yahi 6 naamon mein convert honge.
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_READY_TO_FULFILL = 'READY_TO_FULFILL';
    public const STATUS_DISPATCHED = 'DISPATCHED';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_RETURNED = 'RETURNED';

    /**
     * Shopify ka fulfillment/financial status Finovo canonical status mein badalta hai.
     */
    public static function fromShopify(string $fulfillmentStatus, string $financialStatus): string
    {
        if ($fulfillmentStatus === 'fulfilled') {
            return self::STATUS_DELIVERED;
        }
        if ($financialStatus === 'voided' || $financialStatus === 'refunded') {
            return self::STATUS_CANCELLED;
        }
        if ($financialStatus === 'paid' || $financialStatus === 'partially_paid') {
            return self::STATUS_READY_TO_FULFILL;
        }
        return self::STATUS_PENDING;
    }

    /**
     * WooCommerce ka order status Finovo canonical status mein badalta hai.
     */
    public static function fromWooCommerce(string $wcStatus): string
    {
        return match ($wcStatus) {
            'processing' => self::STATUS_READY_TO_FULFILL,
            'completed' => self::STATUS_DELIVERED,
            'cancelled', 'failed' => self::STATUS_CANCELLED,
            'refunded' => self::STATUS_RETURNED,
            default => self::STATUS_PENDING, // pending, on-hold
        };
    }

    public static function fromCustomBridge(string $bridgeStatus): string
    {
        return match ($bridgeStatus) {
            'confirmed', 'processing' => self::STATUS_READY_TO_FULFILL,
            'delivered', 'completed' => self::STATUS_DELIVERED,
            'cancelled' => self::STATUS_CANCELLED,
            'returned' => self::STATUS_RETURNED,
            default => self::STATUS_PENDING,
        };
    }

    /**
     * Canonical status ko Finovo ke apne internal orders.status column ke
     * values mein badalta hai (jo already database mein use ho rahe hain).
     */
    public static function toInternalOrderStatus(string $canonicalStatus): string
    {
        return match ($canonicalStatus) {
            self::STATUS_READY_TO_FULFILL => 'processing',
            self::STATUS_DISPATCHED => 'processing',
            self::STATUS_DELIVERED => 'delivered',
            self::STATUS_CANCELLED => 'cancelled',
            self::STATUS_RETURNED => 'cancelled',
            default => 'pending',
        };
    }

    public static function paymentStatusFromShopify(string $financialStatus): string
    {
        return in_array($financialStatus, ['paid', 'partially_paid'], true) ? 'paid' : 'unpaid';
    }

    public static function paymentStatusFromWooCommerce(string $wcStatus): string
    {
        return in_array($wcStatus, ['processing', 'completed'], true) ? 'paid' : 'unpaid';
    }
}