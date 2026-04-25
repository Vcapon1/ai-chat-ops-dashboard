<?php
function buildFilters($session_id) {
    $where_conditions = ["c.session_id = ?"];
    $params = [$session_id];

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $_GET['search'];
        $where_conditions[] = "(c.client_name LIKE ? OR c.client_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (isset($_GET['date_start']) && !empty($_GET['date_start'])) {
        $date_start = $_GET['date_start'] . ' 00:00:00';
        $where_conditions[] = "m.created_at >= ?";
        $params[] = $date_start;
    }

    if (isset($_GET['date_end']) && !empty($_GET['date_end'])) {
        $date_end = $_GET['date_end'] . ' 23:59:59';
        $where_conditions[] = "m.created_at <= ?";
        $params[] = $date_end;
    }

    return [
        'conditions' => $where_conditions,
        'params' => $params
    ];
}