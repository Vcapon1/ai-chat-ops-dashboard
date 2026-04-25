
<?php
/**
 * Lead display logic - handles filtering, sorting, and categorization of leads
 */

function getLeadsData($pdo, $session_id, $get_params) {
    // Initialize the variables of arrays before using
    $novosContatos = [];
    $leadsNoCRM = [];
    $leadsDescartados = [];

    // Process filters
    $where_conditions = ["c.session_id = ?"];
    $params = [$session_id];

    if (isset($get_params['search']) && !empty($get_params['search'])) {
        $search = $get_params['search'];
        $where_conditions[] = "(client_name LIKE ? OR client_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (isset($get_params['date_start']) && !empty($get_params['date_start'])) {
        $date_start = $get_params['date_start'] . ' 00:00:00';
        $where_conditions[] = "c.created_at >= ?";
        $params[] = $date_start;
    }

    if (isset($get_params['date_end']) && !empty($get_params['date_end'])) {
        $date_end = $get_params['date_end'] . ' 23:59:59';
        $where_conditions[] = "c.created_at <= ?";
        $params[] = $date_end;
    }

    if (isset($get_params['lead_score']) && !empty($get_params['lead_score'])) {
        $lead_score = $get_params['lead_score'];
        $where_conditions[] = "c.lead_score = ?";
        $params[] = $lead_score;
    }

    if (isset($get_params['status'])) {
        if ($get_params['status'] === 'interested') {
            $where_conditions[] = "c.interessado = 1";
        } elseif ($get_params['status'] === 'not_interested') {
            $where_conditions[] = "c.desinteressado = 1";
        }
    }

    $query = "SELECT c.* FROM clients c";
    $query .= " WHERE " . implode(" AND ", $where_conditions);
    $query .= " ORDER BY c.last_interaction ASC";

    $leads = [];

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Novos Contatos: lead_criado != 1 e não descartados
        $novosContatos = array_filter($leads, function($lead) {
            return $lead['lead_criado'] != 1 && !$lead['desinteressado'];
        });

        // Enviados para CRM: lead_criado == 1
        $leadsNoCRM = array_filter($leads, function($lead) {
            return $lead['lead_criado'] == 1;
        });

        // Descartados: desinteressado = 1
        $leadsDescartados = array_filter($leads, function($lead) {
            return $lead['desinteressado'];
        });

        // Ordenação personalizada para cada coluna
        usort($novosContatos, function($a, $b) {
            return strtotime($b['last_interaction']) - strtotime($a['last_interaction']);
        });
        
        // Reverter a ordem para esta coluna específica - mais antigos primeiro
        usort($leadsNoCRM, function($a, $b) {
            return strtotime($a['last_interaction']) - strtotime($b['last_interaction']);
        });
        
        usort($leadsDescartados, function($a, $b) {
            return strtotime($b['last_interaction']) - strtotime($a['last_interaction']);
        });

    } catch (PDOException $e) {
        error_log("Erro ao buscar leads: " . $e->getMessage());
        die("Erro ao buscar leads.");
    }
    
    return [
        'novosContatos' => $novosContatos,
        'leadsNoCRM' => $leadsNoCRM,
        'leadsDescartados' => $leadsDescartados
    ];
}
?>
