<?php
require_once __DIR__ . '/../app/Models/Customer.php';
require_once __DIR__ . '/../app/Services/OrderImportService.php';
require_once __DIR__ . '/../app/Services/PlatformOrderMapper.php';

use PHPUnit\Framework\TestCase;

/**
 * Customers platform ke orders se bante hain: details sahi nikalti hain, ek hi insaan ka
 * ek hi customer banta hai, aur order customer se jud jata hai.
 */
class CustomerTest extends TestCase
{
    private PDO $db;
    private Customer $customers;
    private int $storeId;
    private string $tag;

    protected function setUp(): void
    {
        $this->db = Database::getConnection();
        $this->customers = new Customer();
        $this->storeId = (int) $this->db->query("SELECT id FROM stores LIMIT 1")->fetchColumn();
        $this->tag = 'phpunit' . bin2hex(random_bytes(3));
    }

    protected function tearDown(): void
    {
        $like = '%' . $this->tag . '%';
        $ids = $this->db->prepare("SELECT id FROM customers WHERE name LIKE ? OR email LIKE ?");
        $ids->execute([$like, $like]);
        $customerIds = $ids->fetchAll(PDO::FETCH_COLUMN);

        $this->db->prepare("DELETE FROM orders WHERE customer_name LIKE ? OR customer_email LIKE ?")->execute([$like, $like]);
        if ($customerIds) {
            $this->db->exec("DELETE FROM customers WHERE id IN (" . implode(',', array_map('intval', $customerIds)) . ")");
        }
        $this->db->prepare("DELETE FROM product_warehouse_stock WHERE product_id IN (SELECT id FROM products WHERE name LIKE ?)")->execute([$like]);
        $this->db->prepare("DELETE FROM products WHERE name LIKE ?")->execute([$like]);
        $this->db->prepare("DELETE FROM integration_errors WHERE external_id LIKE ?")->execute([$like]);
    }

    // ---------------- Platform data → customer details ----------------

    public function testShopifyCustomerDetails(): void
    {
        $c = PlatformOrderMapper::customer('shopify', [
            'email' => 'ali@example.com',
            'customer' => ['id' => 7788, 'first_name' => 'Ali', 'last_name' => 'Raza'],
            'shipping_address' => ['address1' => 'House 5', 'address2' => '', 'city' => 'Karachi', 'zip' => '75500', 'country' => 'Pakistan', 'phone' => '0300-1234567'],
        ]);
        $this->assertSame('Ali Raza', $c['name']);
        $this->assertSame('ali@example.com', $c['email']);
        $this->assertSame('0300-1234567', $c['phone']);
        $this->assertSame('House 5, Karachi, 75500', $c['address']);
        $this->assertSame('Karachi', $c['city']);
        $this->assertSame('Pakistan', $c['country']);
        $this->assertSame('7788', $c['external_customer_id']);
    }

    public function testWooGuestCheckoutHasNoPlatformCustomerId(): void
    {
        $c = PlatformOrderMapper::customer('woocommerce', [
            'customer_id' => 0,
            'billing' => ['first_name' => 'Sara', 'last_name' => 'Khan', 'email' => 'sara@example.com', 'phone' => '0321', 'address_1' => 'Flat 2', 'city' => 'Lahore', 'country' => 'PK'],
            'shipping' => ['address_1' => ''],
        ]);
        $this->assertSame('', $c['external_customer_id']);
        $this->assertSame('Flat 2, Lahore', $c['address']);  // shipping khali → billing address
        $this->assertSame('PK', $c['country']);
    }

    public function testEbayMagentoWixBigCommerceBridge(): void
    {
        $ebay = PlatformOrderMapper::customer('ebay', [
            'buyer' => ['username' => 'buyer_123'],
            'fulfillmentStartInstructions' => [['shippingStep' => ['shipTo' => [
                'fullName' => 'John Smith', 'email' => 'john@x.com', 'primaryPhone' => ['phoneNumber' => '555'],
                'contactAddress' => ['addressLine1' => '1 Main St', 'city' => 'Austin', 'countryCode' => 'US'],
            ]]]],
        ]);
        $this->assertSame('John Smith', $ebay['name']);
        $this->assertSame('buyer_123', $ebay['external_customer_id']);
        $this->assertSame('US', $ebay['country']);

        $magento = PlatformOrderMapper::customer('magento', [
            'customer_email' => 'm@x.com', 'customer_firstname' => 'Mag', 'customer_lastname' => 'Buyer', 'customer_id' => 12,
            'billing_address' => ['street' => ['Road 1', 'Block B'], 'city' => 'Dubai', 'country_id' => 'AE', 'telephone' => '050'],
        ]);
        $this->assertSame('Road 1, Block B, Dubai', $magento['address']);
        $this->assertSame('050', $magento['phone']);

        $wix = PlatformOrderMapper::customer('wix', [
            'buyerInfo' => ['email' => 'w@x.com', 'contactId' => 'c-1'],
            'billingInfo' => ['contactDetails' => ['firstName' => 'Wix', 'lastName' => 'Buyer', 'phone' => '111'], 'address' => ['addressLine1' => 'A St', 'city' => 'Riyadh', 'country' => 'SA']],
        ]);
        $this->assertSame('w@x.com', $wix['email']);
        $this->assertSame('Riyadh', $wix['city']);

        $bc = PlatformOrderMapper::customer('bigcommerce', [
            'customer_id' => 0,
            'billing_address' => ['first_name' => 'B', 'last_name' => 'C', 'email' => 'bc@x.com', 'street_1' => 'S1', 'city' => 'Doha', 'country' => 'Qatar'],
        ]);
        $this->assertSame('', $bc['external_customer_id']);
        $this->assertSame('Qatar', $bc['country']);

        $bridge = PlatformOrderMapper::customer('bridge', ['customer_name' => 'Br', 'customer_email' => 'br@x.com', 'customer_phone' => '9']);
        $this->assertSame('br@x.com', $bridge['email']);
    }

    // ---------------- Ek insaan = ek customer ----------------

    public function testSameEmailIsSameCustomerAndDetailsAreFilledIn(): void
    {
        $email = "{$this->tag}@Example.com";
        $first = $this->customers->upsertFromOrder($this->storeId, "Ali {$this->tag}", ['email' => $email], 'shopify');
        // Doosra order: email chhote huruf mein, ab phone aur address bhi hain
        $second = $this->customers->upsertFromOrder($this->storeId, "Ali R {$this->tag}", ['email' => strtolower($email), 'phone' => '0300', 'address' => 'House 5'], 'shopify');

        $this->assertSame($first, $second);
        $row = $this->customers->find($first);
        $this->assertSame(strtolower($email), $row['email']);
        $this->assertSame('0300', $row['phone']);
        $this->assertSame('House 5', $row['address']);
        $this->assertSame('shopify', $row['source']);

        // Teesra order bina phone ke — purana phone mitta nahi
        $this->customers->upsertFromOrder($this->storeId, "Ali {$this->tag}", ['email' => $email]);
        $this->assertSame('0300', $this->customers->find($first)['phone']);
    }

    public function testPlatformCustomerIdWinsOverEmailAndNameOnlyMatchesWithoutEmail(): void
    {
        $byId = $this->customers->upsertFromOrder($this->storeId, "Sara {$this->tag}", ['external_customer_id' => "X{$this->tag}", 'email' => "old{$this->tag}@x.com"]);
        // Customer ne email badal diya, lekin platform ka customer ID wahi
        $again = $this->customers->upsertFromOrder($this->storeId, "Sara {$this->tag}", ['external_customer_id' => "X{$this->tag}", 'email' => "new{$this->tag}@x.com"]);
        $this->assertSame($byId, $again);

        // Bina email wale do orders same naam se → ek customer
        $a = $this->customers->upsertFromOrder($this->storeId, "Walk-in {$this->tag}");
        $b = $this->customers->upsertFromOrder($this->storeId, "Walk-in {$this->tag}");
        $this->assertSame($a, $b);

        // Same naam lekin email wala → alag customer (do alag log ho sakte hain)
        $c = $this->customers->upsertFromOrder($this->storeId, "Walk-in {$this->tag}", ['email' => "walk{$this->tag}@x.com"]);
        $this->assertNotSame($a, $c);
    }

    // ---------- Edit + kai stores ki list ----------

    public function testEditCustomerDetails(): void
    {
        $id = $this->customers->upsertFromOrder($this->storeId, "Edit {$this->tag}", ['email' => "e{$this->tag}@x.com", 'phone' => '111']);

        $this->customers->updateDetails($id, [
            'name' => "Edited {$this->tag}", 'email' => " NEW{$this->tag}@X.com ", 'phone' => '',
            'address' => 'Street 9', 'city' => 'Lahore', 'country' => 'Pakistan',
        ]);

        $row = $this->customers->find($id);
        $this->assertSame("Edited {$this->tag}", $row['name']);
        $this->assertSame("new{$this->tag}@x.com", $row['email']);   // chhote huruf, spaces hata ke
        $this->assertNull($row['phone']);                             // khali = "Not provided"
        $this->assertSame('Lahore', $row['city']);
    }

    public function testListForSeveralStoresHasStoreName(): void
    {
        $id = $this->customers->upsertFromOrder($this->storeId, "Multi {$this->tag}");

        $rows = array_column($this->customers->allWithStatsForStores([$this->storeId, 999999]), null, 'id');
        $this->assertArrayHasKey($id, $rows);
        $this->assertNotEmpty($rows[$id]['store_name']);

        // Koi store nahi (naya Store Owner) → koi customer nahi
        $this->assertSame([], $this->customers->allWithStatsForStores([]));
    }

    public function testImportedOrderIsLinkedToCustomerWithContactDetails(): void
    {
        $warehouseId = (int) (new Warehouse())->getDefault($this->storeId)['id'];
        $this->db->prepare("INSERT INTO products (store_id, name, price) VALUES (?, ?, ?)")->execute([$this->storeId, "Lamp {$this->tag}", 10]);
        $productId = (int) $this->db->lastInsertId();
        (new Product())->setWarehouseStock($productId, $warehouseId, 5);

        $customer = [
            'email' => "buyer{$this->tag}@x.com", 'phone' => '0333-1111111',
            'address' => 'House 9, Karachi', 'city' => 'Karachi', 'country' => 'Pakistan', 'external_customer_id' => "S{$this->tag}",
        ];
        $importer = new OrderImportService();
        $result = $importer->import($this->storeId, 'shopify_pull', "EXT{$this->tag}", [
            'customer_name' => "Buyer {$this->tag}", 'status' => 'pending', 'payment_status' => 'paid', 'customer' => $customer,
        ], [['name' => "Lamp {$this->tag}", 'quantity' => 1, 'unit_price' => 10]]);
        $this->assertTrue($result['ok']);

        $order = $this->db->query("SELECT * FROM orders WHERE external_order_id = 'EXT{$this->tag}'")->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($order['customer_id']);
        $this->assertSame("buyer{$this->tag}@x.com", $order['customer_email']);
        $this->assertSame('0333-1111111', $order['customer_phone']);
        $this->assertSame('House 9, Karachi, Pakistan', $order['shipping_address']);

        $row = $this->customers->find((int) $order['customer_id']);
        $this->assertSame("Buyer {$this->tag}", $row['name']);
        $this->assertSame('Karachi', $row['city']);
        $this->assertSame('shopify', $row['source']);

        $list = array_values(array_filter($this->customers->allWithStats($this->storeId), fn($c) => (int) $c['id'] === (int) $order['customer_id']));
        $this->assertSame(1, (int) $list[0]['order_count']);
    }
        public function testDeleteOnlyWhenCustomerHasNoOrders(): void
    {
        $free = $this->customers->upsertFromOrder($this->storeId, "Free {$this->tag}");
        $buyer = $this->customers->upsertFromOrder($this->storeId, "Buyer2 {$this->tag}");
        $this->db->prepare("INSERT INTO orders (store_id, customer_name, product_name, quantity, price, source, customer_id) VALUES (?, ?, 'Item', 1, 5, 'manual', ?)")
            ->execute([$this->storeId, "Buyer2 {$this->tag}", $buyer]);

        $this->assertSame(0, $this->customers->orderCount($free));
        $this->assertSame(1, $this->customers->orderCount($buyer));

        // Order wala customer delete NAHI hota (orders ka hisaab na toote)
        $this->assertFalse($this->customers->deleteWithoutOrders($buyer));
        $this->assertNotNull($this->customers->find($buyer));

        // Bina order wala ho jata hai
        $this->assertTrue($this->customers->deleteWithoutOrders($free));
        $this->assertNull($this->customers->find($free));
    }
}