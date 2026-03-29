<?php
header('Content-Type: application/json');
require_once '../../includes/conn.php';

$college_id    = isset($_GET['college_id'])    ? (int)$_GET['college_id']    : 0;
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

if (!$college_id || !$department_id) {
    echo json_encode(['success' => false, 'message' => 'Missing college or department']);
    exit;
}

try {
    /*
     * Join campuses twice:
     *   cpc  = the campus the precinct is directly typed to  (could be Main Campus, WMSU ESU, or a child ESU)
     *   cpar = the parent campus, when cpc has a parent_id   (NULL when cpc is already a root)
     *
     * group_id   = the root campus_id  (parent if one exists, otherwise self)
     * group_name = the root campus_name (e.g. "Main Campus" or "WMSU ESU")
     * sub_name   = the specific child campus name (e.g. "ESU - Alicia"), NULL if no parent
     */
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.location,
            p.current_capacity,
            p.max_capacity,
            p.assignment_status,
            p.college_external,

            cpc.campus_id   AS campus_id,
            cpc.campus_name AS campus_name,
            cpc.parent_id   AS parent_id,

            COALESCE(cpar.campus_id,   cpc.campus_id)   AS group_id,
            COALESCE(cpar.campus_name, cpc.campus_name) AS group_name,

            CASE WHEN cpc.parent_id IS NOT NULL THEN cpc.campus_name ELSE NULL END AS sub_name

        FROM precincts p
        LEFT JOIN campuses cpc  ON p.type        = cpc.campus_id
        LEFT JOIN campuses cpar ON cpc.parent_id = cpar.campus_id

        WHERE p.college    = ?
          AND p.department = ?
          AND p.status     = 'Active'
          AND p.assignment_status = 'unassigned'

        ORDER BY group_name ASC, sub_name ASC, p.name ASC
    ");
    $stmt->execute([$college_id, $department_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Re-structure into groups so the frontend can render section headers cleanly:
     *
     * [
     *   {
     *     "group_id"   : 8,
     *     "group_name" : "Main Campus",
     *     "precincts"  : [ { id, name, location, ... }, ... ]
     *   },
     *   {
     *     "group_id"   : 10,
     *     "group_name" : "WMSU ESU",
     *     "sub_groups" : [
     *       { "sub_id": 12, "sub_name": "ESU - Alicia", "precincts": [...] },
     *       ...
     *       { "sub_id": null, "sub_name": null, "precincts": [...] }   // ESU root (type=10 directly)
     *     ]
     *   }
     * ]
     */
    $groups = [];  // keyed by group_id

    foreach ($rows as $row) {
        $gid  = (int)$row['group_id'];
        $gnam = $row['group_name'];

        if (!isset($groups[$gid])) {
            $groups[$gid] = [
                'group_id'   => $gid,
                'group_name' => $gnam,
                'precincts'  => [],   // for flat groups (Main Campus)
                'sub_groups' => [],   // for ESU children
            ];
        }

        $precinct = [
            'id'               => (int)$row['id'],
            'name'             => $row['name'],
            'location'         => $row['location'],
            'current_capacity' => (int)$row['current_capacity'],
            'max_capacity'     => (int)$row['max_capacity'],
            'assignment_status' => $row['assignment_status'],
            'campus_name'      => $row['campus_name'],
            'sub_name'         => $row['sub_name'],
        ];

        if ($row['sub_name'] !== null) {
            // This precinct belongs to a specific ESU child campus
            $subKey = $row['campus_id'];
            if (!isset($groups[$gid]['sub_groups'][$subKey])) {
                $groups[$gid]['sub_groups'][$subKey] = [
                    'sub_id'   => (int)$row['campus_id'],
                    'sub_name' => $row['sub_name'],
                    'precincts' => [],
                ];
            }
            $groups[$gid]['sub_groups'][$subKey]['precincts'][] = $precinct;
        } else {
            // Precinct is directly under the root campus (e.g., Main Campus or WMSU ESU root)
            $groups[$gid]['precincts'][] = $precinct;
        }
    }

    // Re-index arrays for clean JSON output
    foreach ($groups as &$g) {
        $g['sub_groups'] = array_values($g['sub_groups']);
    }
    unset($g);

    echo json_encode([
        'success' => true,
        'groups'  => array_values($groups),
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
