<?php
require_once '../config/auth.php';
require_login('../login.php');

$user_id = $_SESSION['user_id'];

$search_term = trim($_GET['search'] ?? '');
$selected_brands = [];
if (!empty($_GET['brands'])) {
    $selected_brands = array_map('trim', (array) $_GET['brands']);
}

$selected_fuel_type = trim($_GET['fuel_type'] ?? '');
$selected_min_price = isset($_GET['min_price']) ? (float) $_GET['min_price'] : null;
$selected_max_price = isset($_GET['max_price']) ? (float) $_GET['max_price'] : null;

$price_range_result = $conn->execute_query("SELECT MIN(price_per_day) AS min_price, MAX(price_per_day) AS max_price FROM cars WHERE status = 'available'");
$price_range = $price_range_result->fetch_assoc();

$default_min_price = (float) ($price_range['min_price'] ?? 0);
$default_max_price = (float) ($price_range['max_price'] ?? 0);

if ($default_max_price < $default_min_price) {
    $default_max_price = $default_min_price;
}

if ($selected_min_price === null || $selected_min_price < $default_min_price) {
    $selected_min_price = $default_min_price;
}

if ($selected_max_price === null || $selected_max_price > $default_max_price) {
    $selected_max_price = $default_max_price;
}

if ($selected_max_price < $selected_min_price) {
    $selected_max_price = $selected_min_price;
}

$brands_query = "SELECT DISTINCT brand FROM cars WHERE status = 'available' ORDER BY brand";
$brands_result = $conn->execute_query($brands_query);
$brands = $brands_result->fetch_all(MYSQLI_ASSOC);

$fuel_query = "SELECT DISTINCT fuel_type FROM cars WHERE status = 'available' ORDER BY fuel_type";
$fuel_result = $conn->execute_query($fuel_query);
$fuel_types = $fuel_result->fetch_all(MYSQLI_ASSOC);

$sql = "
    SELECT cars.*, users.name AS owner_name
    FROM cars
    INNER JOIN owners ON cars.owner_id = owners.user_id
    INNER JOIN users ON owners.user_id = users.id
    WHERE cars.status = 'available'
";

$params = [];

if ($search_term !== '') {
    $sql .= " AND (cars.brand LIKE ? OR cars.model LIKE ? OR cars.location LIKE ?)";
    $like_term = "%$search_term%";
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
}

if (!empty($selected_brands)) {
    $placeholders = implode(', ', array_fill(0, count($selected_brands), '?'));
    $sql .= " AND cars.brand IN ($placeholders)";
    foreach ($selected_brands as $brand) {
        $params[] = $brand;
    }
}

if ($selected_fuel_type !== '') {
    $sql .= " AND cars.fuel_type = ?";
    $params[] = $selected_fuel_type;
}

$sql .= " AND cars.price_per_day >= ? AND cars.price_per_day <= ?";
$params[] = $selected_min_price;
$params[] = $selected_max_price;

$sql .= " ORDER BY cars.id DESC";

$cars_result = $conn->execute_query($sql, $params);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Cars</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            margin: 0;
            background: #f4f6f9;
            color: #333;
        }

        .page {
            width: 95%;
            max-width: 1400px;
            margin: 30px auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            background: white;
            border-radius: 16px;
            padding: 18px 22px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .topbar h1 {
            margin: 0;
            font-size: 24px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex: 1;
            justify-content: flex-end;
        }

        .search-form input {
            flex: 1;
            max-width: 420px;
            padding: 12px 14px;
            border: 1px solid #d7dde5;
            border-radius: 10px;
            font-size: 15px;
        }

        .search-form button,
        .filter-actions button,
        .filter-actions a {
            border: none;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .search-form button,
        .filter-actions button {
            background: #0d6efd;
            color: white;
        }

        .content-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
            align-items: start;
        }

        .cars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .car-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        .car-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .car-card .body {
            padding: 16px;
        }

        .car-card h3 {
            margin: 0 0 8px;
            font-size: 20px;
        }

        .meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .price {
            color: #28a745;
            font-size: 20px;
            font-weight: bold;
            margin-top: 12px;
        }

        .details-link {
            display: inline-block;
            margin-top: 14px;
            text-decoration: none;
            color: #0d6efd;
            font-weight: bold;
        }

        .filter-panel {
            background: white;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 20px;
        }

        .filter-panel h3 {
            margin-top: 0;
            margin-bottom: 16px;
        }

        .filter-section {
            border-top: 1px solid #e7ebf0;
            padding: 14px 0;
        }

        .filter-section:first-of-type {
            border-top: none;
            padding-top: 0;
        }

        .filter-section h4 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .checkbox-group,
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .checkbox-group label,
        .radio-group label {
            font-size: 14px;
            color: #555;
        }

        .price-range-inputs {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .price-range-inputs input {
            width: 100%;
            padding: 9px 10px;
            border: 1px solid #d7dde5;
            border-radius: 8px;
        }

        .range-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 6px 0 10px;
        }

        .range-row input[type="range"] {
            width: 100%;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 14px;
        }

        .filter-actions a {
            background: #f1f3f5;
            color: #444;
        }

        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 28px;
            text-align: center;
            color: #666;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 980px) {
            .content-layout {
                grid-template-columns: 1fr;
            }

            .filter-panel {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <form method="get" action="FilterCars.php">
            <div class="topbar">
                <h1>Find your perfect car</h1>
                <div class="search-form">
                    <input type="text" name="search" value="<?= htmlspecialchars($search_term) ?>" placeholder="Search by brand, model or location">
                    <button type="submit">Search</button>
                </div>
            </div>

            <div class="content-layout">
                <div>
                    <?php if (!empty($cars)): ?>
                        <div class="cars-grid">
                            <?php foreach ($cars as $car): ?>
                                <div class="car-card">
                                    <img src="<?= htmlspecialchars($car['image'] ?? '') ?>" alt="Car image">
                                    <div class="body">
                                        <h3><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></h3>
                                        <div class="meta">Year: <?= (int) $car['year'] ?></div>
                                        <div class="meta">Fuel: <?= htmlspecialchars(ucfirst($car['fuel_type'])) ?></div>
                                        <div class="meta">Location: <?= htmlspecialchars($car['location']) ?></div>
                                        <div class="meta">Owner: <?= htmlspecialchars($car['owner_name']) ?></div>
                                        <div class="price">EGP <?= number_format((float) $car['price_per_day'], 2) ?> / day</div>
                                        <a class="details-link" href="CarDetails.php?car_id=<?= (int) $car['id'] ?>">View details</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">No cars match your search. Try changing the filters.</div>
                    <?php endif; ?>
                </div>

                <aside class="filter-panel">
                    <h3>Filter cars</h3>

                    <div class="filter-section">
                        <h4>Brands</h4>
                        <div class="checkbox-group">
                            <?php foreach ($brands as $brand): ?>
                                <?php $brand_name = $brand['brand']; ?>
                                <label>
                                    <input type="checkbox" name="brands[]" value="<?= htmlspecialchars($brand_name) ?>" <?= in_array($brand_name, $selected_brands, true) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($brand_name) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h4>Price range</h4>
                        <div class="range-row">
                            <input type="range" id="minPriceRange" min="<?= (int) $default_min_price ?>" max="<?= (int) $default_max_price ?>" value="<?= (int) $selected_min_price ?>">
                            <input type="range" id="maxPriceRange" min="<?= (int) $default_min_price ?>" max="<?= (int) $default_max_price ?>" value="<?= (int) $selected_max_price ?>">
                        </div>
                        <div class="price-range-inputs">
                            <input type="number" name="min_price" id="minPriceInput" min="<?= (int) $default_min_price ?>" max="<?= (int) $default_max_price ?>" value="<?= (int) $selected_min_price ?>">
                            <input type="number" name="max_price" id="maxPriceInput" min="<?= (int) $default_min_price ?>" max="<?= (int) $default_max_price ?>" value="<?= (int) $selected_max_price ?>">
                        </div>
                    </div>

                    <div class="filter-section">
                        <h4>Fuel type</h4>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="fuel_type" value="" <?= $selected_fuel_type === '' ? 'checked' : '' ?>>
                                Any fuel type
                            </label>
                            <?php foreach ($fuel_types as $fuel): ?>
                                <?php $fuel_name = $fuel['fuel_type']; ?>
                                <label>
                                    <input type="radio" name="fuel_type" value="<?= htmlspecialchars($fuel_name) ?>" <?= $selected_fuel_type === $fuel_name ? 'checked' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($fuel_name)) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit">Apply filters</button>
                        <a href="FilterCars.php">Reset</a>
                    </div>
                </aside>
            </div>
        </form>
    </div>

    <script>
        const minRange = document.getElementById('minPriceRange');
        const maxRange = document.getElementById('maxPriceRange');
        const minInput = document.getElementById('minPriceInput');
        const maxInput = document.getElementById('maxPriceInput');

        function syncFromRange() {
            let minValue = parseInt(minRange.value, 10);
            let maxValue = parseInt(maxRange.value, 10);

            if (minValue > maxValue) {
                minValue = maxValue;
                minRange.value = minValue;
            }

            if (maxValue < minValue) {
                maxValue = minValue;
                maxRange.value = maxValue;
            }

            minInput.value = minValue;
            maxInput.value = maxValue;
        }

        function syncFromInput() {
            let minValue = parseInt(minInput.value, 10);
            let maxValue = parseInt(maxInput.value, 10);

            if (isNaN(minValue)) minValue = parseInt(minRange.min, 10);
            if (isNaN(maxValue)) maxValue = parseInt(maxRange.max, 10);

            if (minValue < parseInt(minRange.min, 10)) minValue = parseInt(minRange.min, 10);
            if (maxValue > parseInt(maxRange.max, 10)) maxValue = parseInt(maxRange.max, 10);
            if (minValue > maxValue) maxValue = minValue;
            if (maxValue < minValue) minValue = maxValue;

            minRange.value = minValue;
            maxRange.value = maxValue;
            minInput.value = minValue;
            maxInput.value = maxValue;
        }

        minRange.addEventListener('input', syncFromRange);
        maxRange.addEventListener('input', syncFromRange);
        minInput.addEventListener('change', syncFromInput);
        maxInput.addEventListener('change', syncFromInput);

        syncFromRange();
    </script>
</body>
</html>