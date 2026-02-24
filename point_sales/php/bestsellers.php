<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "billing_system";


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$month = isset($_GET['month']) ? intval($_GET['month']) : null;
$day = isset($_GET['day']) ? intval($_GET['day']) : null;
$category = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : null;

// Build WHERE clause
$where = [];
$params = [];
if ($year) {
    $where[] = 'YEAR(s.created_at) = :year';
    $params['year'] = $year;
}
if ($month) {
    $where[] = 'MONTH(s.created_at) = :month';
    $params['month'] = $month;
}
if ($day) {
    $where[] = 'DAY(s.created_at) = :day';
    $params['day'] = $day;
}
if ($category) {
    $where[] = 'p.category = :category';
    $params['category'] = $category;
}
$whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Use products (plural) table!
    $sql = "
        SELECT
            p.category,
            si.item_name,
            SUM(si.quantity) AS total_quantity,
            SUM(si.total) AS total_revenue
        FROM sales_items si
        JOIN sales s ON si.sale_id = s.sale_id
        JOIN products p ON si.item_name = p.name
        $whereSql
        GROUP BY p.category, si.item_name
        ORDER BY p.category ASC, total_quantity DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pick best seller per category
    $best_per_category = [];
    foreach ($allRows as $row) {
        $cat = $row['category'];
        if (!isset($best_per_category[$cat])) {
            $best_per_category[$cat] = $row;
        }
    }
    $result = array_values($best_per_category);

    header('Content-Type: application/json');
    echo json_encode($result);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query error: ' . $e->getMessage()]);
}
?>