<?php
require_once __DIR__ . '/config.php';

function get_db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            error_log('DB Connection failed: ' . $conn->connect_error);
            throw new RuntimeException('Database connection failed. Please check your configuration.');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function get_default_tenant_id(): ?string {
    try {
        $db   = get_db();
        $slug = DEFAULT_TENANT_SLUG;
        $stmt = $db->prepare("SELECT id FROM tenants WHERE slug = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return $row ? $row['id'] : null;
    } catch (RuntimeException $e) {
        return null;
    }
}


function get_service_categories(): array {
    try {
        $db        = get_db();
        $tenant_id = get_default_tenant_id();
        if (!$tenant_id) return get_fallback_categories();

        $lang      = CURRENT_LANG;
        $name_col  = "name_{$lang}";
        $title_col = "title_{$lang}";

        $sql = "
            SELECT sc.id AS cat_id, sc.code,
                   COALESCE(sc.{$name_col}, sc.name_en) AS cat_name,
                   gs.id AS svc_id,
                   COALESCE(gs.{$title_col}, gs.title_en) AS svc_title,
                   gs.estimated_processing_time,
                   gs.estimated_fees
            FROM service_categories sc
            LEFT JOIN government_services gs
                   ON gs.category_id = sc.id AND gs.is_published = 1
            WHERE sc.tenant_id = ?
            ORDER BY sc.code, gs.title_en
        ";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $tenant_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $categories = [];
        foreach ($rows as $row) {
            $cid = $row['cat_id'];
            if (!isset($categories[$cid])) {
                $categories[$cid] = [
                    'id'       => $cid,
                    'code'     => $row['code'],
                    'name'     => $row['cat_name'],
                    'services' => [],
                ];
            }
            if ($row['svc_id']) {
                $categories[$cid]['services'][] = [
                    'id'              => $row['svc_id'],
                    'title'           => $row['svc_title'],
                    'processing_time' => $row['estimated_processing_time'],
                    'fees'            => $row['estimated_fees'],
                ];
            }
        }
        return array_values($categories) ?: get_fallback_categories();
    } catch (RuntimeException $e) {
        return get_fallback_categories();
    }
}

function get_fallback_categories(): array {

    // gov service options hardcoded

    $t = CURRENT_LANG;
    return [
        ['id'=>'1','code'=>'civil-registry','name'=> $t==='fil'?'Rehistro Sibil':($t==='mrw'?'Civil Registry':'Civil Registry'),'services'=>[
            ['id'=>'s1','title'=>$t==='fil'?'Pagpapatala ng Kapanganakan':'Birth Certificate Registration','processing_time'=>'3-5 days','fees'=>'0.00'],
            ['id'=>'s2','title'=>$t==='fil'?'Pagpapatala ng Kasal':'Marriage Registration','processing_time'=>'3-5 days','fees'=>'0.00'],
        ]],
        ['id'=>'2','code'=>'business-permit','name'=>$t==='fil'?'Permit sa Negosyo':'Business Permit','services'=>[
            ['id'=>'s3','title'=>$t==='fil'?'Bagong Permit sa Negosyo':'New Business Permit','processing_time'=>'5-7 days','fees'=>'500.00'],
            ['id'=>'s4','title'=>$t==='fil'?'Pagpapaliban ng Permit':'Permit Renewal','processing_time'=>'3-5 days','fees'=>'300.00'],
        ]],
        ['id'=>'3','code'=>'tricycle-franchise','name'=>$t==='fil'?'Frantsisa ng Tricycle':'Tricycle Franchise','services'=>[
            ['id'=>'s5','title'=>$t==='fil'?'Bagong Frantsisa ng Tricycle':'New Tricycle Franchise','processing_time'=>'7-10 days','fees'=>'200.00'],
        ]],
        ['id'=>'4','code'=>'scholarships','name'=>$t==='fil'?'Iskolarship':'Scholarships','services'=>[
            ['id'=>'s6','title'=>$t==='fil'?'Iskolarship sa Pamahalaang Lungsod':'City Government Scholarship','processing_time'=>'14 days','fees'=>'0.00'],
        ]],
        ['id'=>'5','code'=>'barangay-clearance','name'=>$t==='fil'?'Barangay Clearance':'Barangay Clearance','services'=>[
            ['id'=>'s7','title'=>$t==='fil'?'Barangay Clearance':'Barangay Clearance','processing_time'=>'1 day','fees'=>'50.00'],
        ]],
        ['id'=>'6','code'=>'cedula','name'=>$t==='fil'?'Cedula / CTC':'Community Tax Certificate','services'=>[
            ['id'=>'s8','title'=>$t==='fil'?'Komunidad na Sertipiko ng Buwis':'Community Tax Certificate (Cedula)','processing_time'=>'Same day','fees'=>'Varies'],
        ]],


    ];
}


function log_chat(string $tenant_id, ?string $bot_user_id, string $user_msg, string $bot_response, ?string $intent, string $lang, float $confidence = 0.0, bool $is_fallback = false): void {
    try {
        $db   = get_db();
        $stmt = $db->prepare("
            INSERT INTO chat_logs (tenant_id, bot_user_id, user_message, bot_response, detected_intent, detected_language, confidence_score, is_fallback)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $fb   = $is_fallback ? 1 : 0;
        $stmt->bind_param('ssssssdi', $tenant_id, $bot_user_id, $user_msg, $bot_response, $intent, $lang, $confidence, $fb);
        $stmt->execute();
    } catch (RuntimeException $e) {
        // Silent fail — if not functionaing or the expected result is not met bh.
    }
}
