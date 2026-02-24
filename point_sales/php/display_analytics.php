<?php
// Database connection
$host = 'localhost';
$dbname = 'billing_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get filters from GET
$season = $_GET['season'] ?? '';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

if (!$season || !$year) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Refresh analytics_sales table (Now with year column)
try {
    $pdo->exec("TRUNCATE TABLE analytics_sales");
    $refreshQuery = "
        INSERT INTO analytics_sales (season, year, item_name, total_quantity, total_revenue)
        SELECT 
            CASE 
                WHEN MONTH(s.created_at) IN (1, 2, 3, 4, 5) THEN 'Summer'
                WHEN MONTH(s.created_at) IN (6, 7, 8) THEN 'Rainy'
                WHEN MONTH(s.created_at) IN (9, 10, 11, 12) THEN 'BER'
            END AS season,
            YEAR(s.created_at) AS year,
            si.item_name,
            SUM(si.quantity) AS total_quantity,
            SUM(si.total) AS total_revenue
        FROM 
            sales_items si
        JOIN 
            sales s ON si.sale_id = s.sale_id
        GROUP BY 
            season, year, si.item_name;
    ";
    $pdo->exec($refreshQuery);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to refresh analytics table: ' . $e->getMessage()]);
    exit;
}

// Query to fetch analytics data for the selected season and year
try {
    $query = "SELECT item_name, total_quantity, total_revenue FROM analytics_sales WHERE season = :season AND year = :year ORDER BY total_revenue DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['season' => $season, 'year' => $year]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to fetch analytics data: ' . $e->getMessage()]);
}
?>