<?php

$baseUrl =
    '/finovo-oms-and-wms/public/index.php';

$title =
    'Add Order';

?>


<div class="breadcrumb">

    Finovo

    <i class="bi bi-chevron-right"></i>

    <a
        href="<?= $baseUrl ?>/orders"
        style="
            color:inherit;
            text-decoration:none;
        ">
        Orders
    </a>

    <i class="bi bi-chevron-right"></i>

    Add Order

</div>



<div
    style="
        max-width:900px;
        margin:0 auto;
    ">


    <div
        style="
            margin-bottom:24px;
        ">

        <h1
            style="
                font-size:24px;
                font-weight:600;
                color:#111827;
                margin:0 0 6px;
            ">

            Add New Order

        </h1>


        <p
            style="
                margin:0;
                color:#6b7280;
                font-size:14px;
            ">

            Create a test order to generate
            and download an invoice.

        </p>

    </div>



    <?php if (!empty($error)): ?>

        <div
            class="banner banner-error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>



    <form
        method="POST"
        action="<?= $baseUrl ?>/orders/create">



        <div
            class="card"
            style="
                padding:24px;
                margin-bottom:20px;
                overflow:visible;
            ">


            <h2
                style="
                    font-size:16px;
                    font-weight:600;
                    margin:0 0 20px;
                ">

                Customer Information

            </h2>



            <div
                style="
                    display:grid;
                    grid-template-columns:
                    repeat(2, minmax(0, 1fr));
                    gap:16px;
                ">


                <div class="form-group">

                    <label>
                        Customer Name *
                    </label>

                    <input
                        type="text"
                        name="customer_name"
                        required>

                </div>



                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="customer_email">

                </div>



                <div class="form-group">

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        name="customer_phone">

                </div>


            </div>



            <div
                style="
                    display:grid;
                    grid-template-columns:
                    repeat(2, minmax(0, 1fr));
                    gap:16px;
                ">


                <div class="form-group">

                    <label>
                        Billing Address
                    </label>

                    <textarea
                        name="billing_address"
                        rows="3"
                        style="
                            width:100%;
                            border:1px solid #e5e7eb;
                            border-radius:8px;
                            padding:9px 10px;
                            resize:vertical;
                            outline:none;
                        "></textarea>

                </div>



                <div class="form-group">

                    <label>
                        Shipping Address
                    </label>

                    <textarea
                        name="shipping_address"
                        rows="3"
                        style="
                            width:100%;
                            border:1px solid #e5e7eb;
                            border-radius:8px;
                            padding:9px 10px;
                            resize:vertical;
                            outline:none;
                        "></textarea>

                </div>


            </div>


        </div>




        <div
            class="card"
            style="
                padding:24px;
                margin-bottom:20px;
                overflow:visible;
            ">


            <h2
                style="
                    font-size:16px;
                    font-weight:600;
                    margin:0 0 20px;
                ">

                Product Information

            </h2>



            <div
                style="
                    display:grid;
                    grid-template-columns:
                    repeat(2, minmax(0, 1fr));
                    gap:16px;
                ">


                <div class="form-group">

                    <label>
                        Product Name *
                    </label>

                    <input
                        type="text"
                        name="product_name"
                        required>

                </div>



                <div class="form-group">

                    <label>
                        SKU
                    </label>

                    <input
                        type="text"
                        name="sku"
                        placeholder="TS-001">

                </div>



                <div class="form-group">

                    <label>
                        Variant
                    </label>

                    <input
                        type="text"
                        name="variant"
                        placeholder="Black / Large">

                </div>



                <div class="form-group">

                    <label>
                        Quantity *
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        value="1"
                        min="1"
                        required>

                </div>



                <div class="form-group">

                    <label>
                        Unit Price (PKR) *
                    </label>

                    <input
                        type="number"
                        name="unit_price"
                        step="0.01"
                        min="0.01"
                        placeholder="2500"
                        required>

                </div>


            </div>


        </div>




        <div
            class="card"
            style="
                padding:24px;
                margin-bottom:20px;
                overflow:visible;
            ">


            <h2
                style="
                    font-size:16px;
                    font-weight:600;
                    margin:0 0 20px;
                ">

                Charges & Payment

            </h2>



            <div
                style="
                    display:grid;
                    grid-template-columns:
                    repeat(2, minmax(0, 1fr));
                    gap:16px;
                ">


                <div class="form-group">

                    <label>
                        Discount
                    </label>

                    <input
                        type="number"
                        name="discount"
                        value="0"
                        min="0"
                        step="0.01">

                </div>



                <div class="form-group">

                    <label>
                        Shipping
                    </label>

                    <input
                        type="number"
                        name="shipping_cost"
                        value="0"
                        min="0"
                        step="0.01">

                </div>



                <div class="form-group">

                    <label>
                        Tax
                    </label>

                    <input
                        type="number"
                        name="tax"
                        value="0"
                        min="0"
                        step="0.01">

                </div>



                <div class="form-group">

                    <label>
                        Payment Method
                    </label>

                    <select
                        name="payment_method"
                        style="
                            width:100%;
                            border:1px solid #e5e7eb;
                            border-radius:8px;
                            padding:9px 10px;
                            background:white;
                        ">

                        <option value="">
                            Select payment method
                        </option>

                        <option value="Cash on Delivery">
                            Cash on Delivery
                        </option>

                        <option value="Card">
                            Card
                        </option>

                        <option value="Bank Transfer">
                            Bank Transfer
                        </option>

                        <option value="PayPal">
                            PayPal
                        </option>

                    </select>

                </div>



                <div class="form-group">

                    <label>
                        Payment Status
                    </label>

                    <select
                        name="payment_status"
                        style="
                            width:100%;
                            border:1px solid #e5e7eb;
                            border-radius:8px;
                            padding:9px 10px;
                            background:white;
                        ">

                        <option value="Pending">
                            Pending
                        </option>

                        <option value="Paid">
                            Paid
                        </option>

                        <option value="Failed">
                            Failed
                        </option>

                        <option value="Refunded">
                            Refunded
                        </option>

                    </select>

                </div>


            </div>


        </div>




        <div
            style="
                display:flex;
                justify-content:flex-end;
                align-items:center;
                gap:10px;
            ">


            <a
                href="<?= $baseUrl ?>/orders"
                class="btn-secondary"
                style="
                    text-decoration:none;
                    display:inline-flex;
                    align-items:center;
                ">

                Cancel

            </a>


            <button
                type="submit"
                class="btn-primary">

                <i class="bi bi-plus-lg"></i>

                Create Order

            </button>


        </div>


    </form>


</div>



<style>
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="number"] {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 9px 10px;
        font-size: 14px;
        outline: none;
    }


    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: #111827;
    }


    @media (max-width:700px) {

        form .card>div {
            grid-template-columns: 1fr !important;
        }

    }
</style>