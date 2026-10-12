<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_admin();

$pageTitle = 'API Data Integration';
$activePage = '';

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">

    <!-- Page Header -->
    <div class="text-center mb-5">
        <span class="badge bg-dark mb-3">
            API / DATA INTEGRATION
        </span>

        <h1 class="fw-bold">
            Custom Case Co. API
        </h1>

        <p class="text-muted">
            Product and customer information retrieved using GET requests.
        </p>
    </div>


    <!-- PRODUCTS -->
    <section class="mb-5">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 fw-bold mb-1">
                    <i class="bi bi-box-seam"></i>
                    Products
                </h2>

                <p class="text-muted mb-0">
                    Data retrieved from the Products API.
                </p>
            </div>

            <span class="badge bg-primary">
                GET /api/products.php
            </span>
        </div>

        <div id="productsLoading" class="text-center py-4">
            <div class="spinner-border"></div>
            <p class="text-muted mt-2">
                Loading products...
            </p>
        </div>

        <div id="productsError" class="alert alert-danger d-none"></div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle d-none"
                   id="productsTable">

                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Phone Model</th>
                        <th>Price</th>
                        <th>Stock</th>
                    </tr>
                </thead>

                <tbody id="productsBody"></tbody>

            </table>
        </div>

    </section>


    <!-- CUSTOMERS -->
    <section>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 fw-bold mb-1">
                    <i class="bi bi-people"></i>
                    Customers
                </h2>

                <p class="text-muted mb-0">
                    Data retrieved from the Customers API.
                </p>
            </div>

            <span class="badge bg-success">
                GET /api/customers.php
            </span>
        </div>

        <div id="customersLoading" class="text-center py-4">
            <div class="spinner-border"></div>
            <p class="text-muted mt-2">
                Loading customers...
            </p>
        </div>

        <div id="customersError" class="alert alert-danger d-none"></div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle d-none"
                   id="customersTable">

                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registered</th>
                    </tr>
                </thead>

                <tbody id="customersBody"></tbody>

            </table>
        </div>

    </section>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    loadProducts();
    loadCustomers();

});


/* ==========================================
   PRODUCTS API
========================================== */

function loadProducts() {

    fetch('<?= BASE_URL ?>/api/products.php', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })

    .then(function (response) {

        if (!response.ok) {
            throw new Error('Products API returned an error.');
        }

        return response.json();

    })

    .then(function (result) {

        document.getElementById('productsLoading')
            .classList.add('d-none');

        if (!result.success) {
            throw new Error(
                result.message || 'Unable to load products.'
            );
        }

        var tbody = document.getElementById('productsBody');

        tbody.innerHTML = '';

        result.data.forEach(function (product) {

            var row = document.createElement('tr');

            row.innerHTML = `
                <td>${escapeHtml(product.id)}</td>

                <td>
                    <strong>
                        ${escapeHtml(product.name)}
                    </strong>
                </td>

                <td>
                    ${escapeHtml(product.category || 'N/A')}
                </td>

                <td>
                    ${escapeHtml(product.brand || 'N/A')}
                </td>

                <td>
                    ${escapeHtml(product.phone_model || 'N/A')}
                </td>

                <td>
                    ₱${Number(product.price).toFixed(2)}
                </td>

                <td>
                    ${escapeHtml(product.stock)}
                </td>
            `;

            tbody.appendChild(row);

        });

        document.getElementById('productsTable')
            .classList.remove('d-none');

    })

    .catch(function (error) {

        document.getElementById('productsLoading')
            .classList.add('d-none');

        var errorBox = document.getElementById('productsError');

        errorBox.textContent = error.message;
        errorBox.classList.remove('d-none');

    });

}


/* ==========================================
   CUSTOMERS API
========================================== */

function loadCustomers() {

    fetch('<?= BASE_URL ?>/api/customers.php', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })

    .then(function (response) {

        if (!response.ok) {
            throw new Error('Customers API returned an error.');
        }

        return response.json();

    })

    .then(function (result) {

        document.getElementById('customersLoading')
            .classList.add('d-none');

        if (!result.success) {
            throw new Error(
                result.message || 'Unable to load customers.'
            );
        }

        var tbody = document.getElementById('customersBody');

        tbody.innerHTML = '';

        result.data.forEach(function (customer) {

            var row = document.createElement('tr');

            row.innerHTML = `
                <td>${escapeHtml(customer.id)}</td>

                <td>
                    <strong>
                        ${escapeHtml(customer.full_name)}
                    </strong>
                </td>

                <td>
                    ${escapeHtml(customer.username)}
                </td>

                <td>
                    ${escapeHtml(customer.email)}
                </td>

                <td>
                    ${escapeHtml(customer.phone || 'N/A')}
                </td>

                <td>
                    ${escapeHtml(customer.created_at)}
                </td>
            `;

            tbody.appendChild(row);

        });

        document.getElementById('customersTable')
            .classList.remove('d-none');

    })

    .catch(function (error) {

        document.getElementById('customersLoading')
            .classList.add('d-none');

        var errorBox = document.getElementById('customersError');

        errorBox.textContent = error.message;
        errorBox.classList.remove('d-none');

    });

}


/* ==========================================
   BASIC HTML ESCAPING
========================================== */

function escapeHtml(value) {

    var div = document.createElement('div');

    div.textContent = value ?? '';

    return div.innerHTML;

}

</script>


<?php include __DIR__ . '/includes/footer.php'; ?>