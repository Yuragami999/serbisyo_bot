<?php
// ============================================================
// SerbisyoBot - chat-handler.php
// Secure server-side AWS Bedrock integration endpoint.
// Called via AJAX from app.js — never exposes credentials.
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// --- Only accept POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// --- Parse JSON body ---
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!$input || empty($input['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing message payload.']);
    exit;
}

$user_message = trim(strip_tags($input['message']));
$lang         = in_array($input['language'] ?? '', ['en', 'fil', 'mrw']) ? $input['language'] : 'fil';

if (mb_strlen($user_message) > 1000) {
    $user_message = mb_substr($user_message, 0, 1000);
}

// --- Build the System Prompt with Marawi City LGU context ---
$system_prompt = build_system_prompt($lang);

// --- Call Amazon Bedrock ---
$result = call_bedrock($system_prompt, $user_message, $lang);

// --- Log to Database ---
$tenant_id = get_default_tenant_id();
if ($tenant_id) {
    $bot_user_id = $_SESSION['bot_user_id'] ?? null;
    log_chat(
        $tenant_id,
        $bot_user_id,
        $user_message,
        $result['response'],
        $result['intent'] ?? null,
        $lang,
        $result['confidence'] ?? 0.85,
        $result['is_fallback'] ?? false
    );
}

echo json_encode([
    'response'    => $result['response'],
    'intent'      => $result['intent'] ?? null,
    'language'    => $lang,
    'is_fallback' => $result['is_fallback'] ?? false,
]);
exit;


function build_system_prompt(string $lang): string {
    $lang_instructions = match($lang) {
        'fil' => 'Respond ONLY in Filipino (Tagalog). Be warm, helpful, and formal but approachable.',
        'mrw' => 'Respond ONLY in Meranao language. Be respectful, warm, and helpful. Use "Assalamu Alaikum" as a greeting when appropriate.',
        default => 'Respond ONLY in English. Be warm, professional, and helpful.',
    };

    return <<<PROMPT
You are SerbisyoBot, the official AI-powered government services assistant for Marawi City, BARMM (Bangsamoro Autonomous Region in Muslim Mindanao), Philippines.

LANGUAGE INSTRUCTION: {$lang_instructions}

YOUR ROLE:
- Help citizens of Marawi City find information about government services, document requirements, office locations, fees, and processing times.
- You are strictly a public service information assistant. Do NOT discuss unrelated topics.
- Always be respectful of the local culture, Islamic values, and the Maranao people's traditions.

MARAWI CITY LGU KNOWLEDGE BASE:

1. CIVIL REGISTRY (City Civil Registrar's Office, City Hall Ground Floor)
   - Birth Certificate Registration: Free, 3-5 working days. Requirements: Hospital birth record, PSA form, barangay certification, valid IDs of parents.
   - Marriage Registration: Free, 3-5 working days. Requirements: Marriage license, CENOMAR, valid IDs, barangay clearances.
   - Death Certificate: Free, 1-3 working days. Requirements: Death report from hospital/barangay, burial permit.

2. BUSINESS PERMITS (Business Permits & Licensing Office, City Hall 2nd Floor)
   - New Business Permit: PHP 500.00 base fee + regulatory fees, 5-7 working days. Requirements: DTI/SEC registration, barangay clearance, lease contract or property title, valid ID, filled application form.
   - Business Permit Renewal: PHP 300.00 base fee, 3-5 working days. Requirements: Previous year's permit, updated barangay clearance, tax clearance.

3. TRICYCLE FRANCHISE (Traffic & Transport Management Office)
   - New Tricycle Franchise: PHP 200.00, 7-10 working days. Requirements: OR/CR of unit, driver's license (professional), barangay endorsement, insurance, LTFRB accreditation form.
   - Franchise Renewal: PHP 150.00, 5-7 working days.

4. SCHOLARSHIPS (City Social Welfare & Development Office / CHED Desk)
   - City Government Scholarship: Free application, 14 working days evaluation. Requirements: Copy of grades (GWA 85+), barangay indigency certificate, PSA birth certificate, 2x2 photo, acceptance letter from school.

5. BARANGAY CLEARANCE (Respective Barangay Hall)
   - PHP 50.00, same day to 1 working day. Requirements: Valid government ID, proof of residency (utility bill), filled application form.

6. COMMUNITY TAX CERTIFICATE / CEDULA (City Treasurer's Office or Barangay)
   - Fee based on income (minimum PHP 5.00), same day. Requirements: Valid ID only.

IMPORTANT GUIDELINES:
- If asked something outside your knowledge base, politely say you don't have that information and suggest the citizen call the Marawi City Hall hotline or visit in person.
- Marawi City Hall contact: (063) 352-2020 (general)
- City Hall is open Monday–Friday, 8:00 AM – 5:00 PM (except holidays)
- Always end responses with an offer to help further.
- Keep responses concise but complete. Use numbered lists for requirements.
PROMPT;
}

/**
 * Sign and send a request to Amazon Bedrock using AWS Signature Version 4.
 * Uses native PHP cURL — no SDK dependency required.
 */
function call_bedrock(string $system_prompt, string $user_message, string $lang): array {
    // If credentials are placeholders, return a graceful demo response
    if (AWS_ACCESS_KEY_ID === 'YOUR_AWS_ACCESS_KEY_ID') {
        return get_demo_response($user_message, $lang);
    }

    $region     = AWS_REGION;
    $service    = 'bedrock-runtime';
    $model_id   = BEDROCK_MODEL_ID;
    $endpoint   = "https://{$service}.{$region}.amazonaws.com/model/{$model_id}/invoke";

    $request_body = json_encode([
        'anthropic_version' => 'bedrock-2023-05-31',
        'max_tokens'        => 1024,
        'system'            => $system_prompt,
        'messages'          => [
            ['role' => 'user', 'content' => $user_message]
        ],
    ]);

    // --- AWS Signature V4 ---
    $datetime  = gmdate('Ymd\THis\Z');
    $date      = substr($datetime, 0, 8);
    $headers   = [
        'content-type' => 'application/json',
        'host'         => "{$service}.{$region}.amazonaws.com",
        'x-amz-date'   => $datetime,
    ];

    $canonical_headers = '';
    $signed_headers    = '';
    ksort($headers);
    foreach ($headers as $k => $v) {
        $canonical_headers .= "{$k}:{$v}\n";
        $signed_headers    .= "{$k};";
    }
    $signed_headers = rtrim($signed_headers, ';');

    $payload_hash      = hash('sha256', $request_body);
    $canonical_uri     = "/model/{$model_id}/invoke";
    $canonical_request = implode("\n", [
        'POST',
        $canonical_uri,
        '',
        $canonical_headers,
        $signed_headers,
        $payload_hash,
    ]);

    $credential_scope = "{$date}/{$region}/{$service}/aws4_request";
    $string_to_sign   = implode("\n", [
        'AWS4-HMAC-SHA256',
        $datetime,
        $credential_scope,
        hash('sha256', $canonical_request),
    ]);

    $signing_key = hmac_sha256(
        hmac_sha256(
            hmac_sha256(
                hmac_sha256('AWS4' . AWS_SECRET_ACCESS_KEY, $date, true),
                $region, true
            ),
            $service, true
        ),
        'aws4_request', true
    );

    $signature    = bin2hex(hmac_sha256($signing_key, $string_to_sign, true));
    $auth_header  = "AWS4-HMAC-SHA256 Credential=" . AWS_ACCESS_KEY_ID . "/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";

    // --- cURL request ---
    $curl_headers = ["Authorization: {$auth_header}"];
    foreach ($headers as $k => $v) {
        $curl_headers[] = "{$k}: {$v}";
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $request_body,
        CURLOPT_HTTPHEADER     => $curl_headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $resp_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err || $http_code !== 200) {
        error_log("Bedrock error HTTP {$http_code}: {$curl_err} | {$resp_body}");
        return [
            'response'    => get_error_message($lang),
            'is_fallback' => true,
            'confidence'  => 0.0,
        ];
    }

    $decoded   = json_decode($resp_body, true);
    $ai_text   = $decoded['content'][0]['text'] ?? get_error_message($lang);

    return [
        'response'    => $ai_text,
        'intent'      => detect_intent($user_message),
        'confidence'  => 0.9,
        'is_fallback' => false,
    ];
}

function hmac_sha256(string $key, string $data, bool $raw = false): string {
    return hash_hmac('sha256', $data, $key, $raw);
}

/**
 * Simple keyword-based intent detection.
 */
function detect_intent(string $message): string {
    $msg = mb_strtolower($message);
    if (str_contains($msg, 'birth') || str_contains($msg, 'kapanganakan') || str_contains($msg, 'pagkatao'))
        return 'civil_registry.birth_certificate';
    if (str_contains($msg, 'business') || str_contains($msg, 'negosyo') || str_contains($msg, 'permit'))
        return 'business_permit';
    if (str_contains($msg, 'tricycle') || str_contains($msg, 'franchise') || str_contains($msg, 'frantsisa'))
        return 'tricycle_franchise';
    if (str_contains($msg, 'scholar') || str_contains($msg, 'iskolar'))
        return 'scholarship';
    if (str_contains($msg, 'barangay') || str_contains($msg, 'clearance'))
        return 'barangay_clearance';
    if (str_contains($msg, 'cedula') || str_contains($msg, 'community tax') || str_contains($msg, 'ctc'))
        return 'community_tax';
    return 'general_inquiry';
}

/**
 * Demo response when AWS credentials are not yet configured.
 */
function get_demo_response(string $message, string $lang): array {
    $intent = detect_intent($message);

    $responses = [
        'fil' => [
            'civil_registry.birth_certificate' => "Para sa **Sertipiko ng Kapanganakan**, kailangan mo ng:\n\n1. Record ng kapanganakan mula sa ospital\n2. PSA form (mula sa Civil Registrar)\n3. Sertipikasyon mula sa barangay\n4. Mga valid ID ng mga magulang\n\n**Opisina:** Civil Registrar's Office, Lungsod ng Marawi, Ground Floor ng City Hall\n**Bayad:** Libre\n**Oras ng Pagproseso:** 3-5 araw na trabaho\n\nMayroon ka pa bang ibang katanungan?",

            'business_permit' => "Para sa **Permit sa Negosyo**, ang mga kinakailangan ay:\n\n1. Rehistrasyon ng DTI o SEC\n2. Barangay clearance\n3. Kontrata ng paupahan o titulo ng ari-arian\n4. Valid ID\n5. Natapos na application form\n\n**Opisina:** Business Permits & Licensing Office, 2nd Floor City Hall\n**Bayad:** PHP 500.00 (base fee)\n**Oras ng Pagproseso:** 5-7 araw na trabaho\n\nMayroon ka pa bang ibang katanungan?",

            'default' => "Kumusta! Ako si SerbisyoBot. Maaari mo akong tanungin tungkol sa mga serbisyo ng Lungsod ng Marawi tulad ng:\n\n• Sertipiko ng Kapanganakan\n• Permit sa Negosyo\n• Frantsisa ng Tricycle\n• Iskolarship\n• Barangay Clearance\n• Cedula\n\nAno ang iyong katanungan?",
        ],
        'mrw' => [
            'default' => "Assalamu Alaikum! Ako si SerbisyoBot. Makapakisabi ka kaniyak ko serbisyo sa Ranao a Baya. Antonaa so tabangan tano anka?", 
        ],
        'en' => [
            'default' => "Hello! I'm SerbisyoBot. I can help you with Marawi City government services like Birth Certificates, Business Permits, Tricycle Franchises, Scholarships, Barangay Clearances, and more. What would you like to know?",
        ],
    ];

    $lang_responses = $responses[$lang] ?? $responses['fil'];
    $response_text  = $lang_responses[$intent] ?? $lang_responses['default'] ?? $responses['fil']['default'];

    return [
        'response'    => $response_text . "\n\n*[Demo Mode — Configure AWS credentials in includes/config.php to enable live AI responses]*",
        'intent'      => $intent,
        'confidence'  => 0.8,
        'is_fallback' => false,
    ];
}

/**
 * Localized error message.
 */
function get_error_message(string $lang): string {
    return match($lang) {
        'mrw' => 'Pasensya, mayda problema sa aking sistema. Pakitawagan so City Hall sa (063) 352-2020.',
        'fil' => 'Paumanhin, nagkaroon ng problema sa aking sistema. Mangyaring tumawag sa City Hall sa (063) 352-2020.',
        default => 'Sorry, I encountered a problem. Please call Marawi City Hall at (063) 352-2020.',
    };
}
