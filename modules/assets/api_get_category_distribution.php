<?php
require_once '../../includes/db.php';
require_once '../../includes/auth_functions.php';

header('Content-Type: application/json');

check_auth();
check_role(['super_admin', 'admin']);

$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;

$category_colors = [
  '#3b82f6', // blue
  '#8b5cf6', // purple
  '#ec4899', // pink
  '#f59e0b', // amber
  '#10b981', // green
  '#06b6d4', // cyan
  '#f97316', // orange
  '#6366f1', // indigo
];

$categories = [];

// Build query based on location filter
$category_dist_query = "
  SELECT 
    ac.category_name,
    COUNT(a.asset_id) as asset_count
  FROM asset_categories ac
  LEFT JOIN assets a ON ac.category_id = a.category_id AND a.status != 'Removed'
";

if ($location_id > 0) {
  $category_dist_query .= " AND a.location_id = " . $location_id;
}

$category_dist_query .= "
  WHERE ac.is_active = 1
  GROUP BY ac.category_id, ac.category_name
  HAVING asset_count > 0
  ORDER BY asset_count DESC
";

if ($category_dist_result = mysqli_query($conn, $category_dist_query)) {
  $total_for_distribution = 0;
  $temp_categories = [];

  while ($row = mysqli_fetch_assoc($category_dist_result)) {
    $temp_categories[] = $row;
    $total_for_distribution += $row['asset_count'];
  }

  // Add total and color to each category
  $color_index = 0;
  foreach ($temp_categories as $cat) {
    $categories[] = [
      'name' => $cat['category_name'],
      'count' => $cat['asset_count'],
      'total' => $total_for_distribution,
      'color' => $category_colors[$color_index % count($category_colors)]
    ];
    $color_index++;
  }

  mysqli_free_result($category_dist_result);

  echo json_encode([
    'success' => true,
    'categories' => $categories
  ]);
} else {
  echo json_encode([
    'success' => false,
    'error' => mysqli_error($conn)
  ]);
}

mysqli_close($conn);
?>