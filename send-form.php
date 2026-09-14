<?php
header('Content-Type: application/json; charset=utf-8');

function clean_value($value) {
    if ($value === null) {
        return '';
    }
    return trim((string) $value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Método no permitido.'
    ]);
    exit;
}

$requiredFields = [
    'directorName',
    'directorEmail',
    'directorPhone',
    'institution',
    'filmTitle',
    'filmDuration',
    'filmTechnique',
    'filmSynopsis',
    'cloudLink'
];

$values = [];
foreach ($requiredFields as $field) {
    $values[$field] = clean_value($_POST[$field] ?? '');
}

foreach ($values as $key => $value) {
    if ($value === '') {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => 'Falta completar el campo: ' . $key
        ]);
        exit;
    }
}

if (!filter_var($values['directorEmail'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Correo de contacto inválido.'
    ]);
    exit;
}

$to = 'pablouzi@gmail.com';
$subject = '[POSTULACIÓN FAIN 2026] ' . $values['filmTitle'] . ' - ' . $values['directorName'];
$now = new DateTime('now', new DateTimeZone('America/Santiago'));
$timestamp = $now->format('d-m-Y H:i:s');
$receiptId = 'FAIN2026-' . strtoupper(bin2hex(random_bytes(3)));
$hasLocalIdentity = clean_value($_POST['hasLocalIdentity'] ?? 'No especificado');
$checkAiRule = clean_value($_POST['checkAiRule'] ?? '');
$checkRights = clean_value($_POST['checkRights'] ?? '');

$message = "==============================================\n";
$message .= "    COMPROBANTE DE POSTULACIÓN - FAIN 2026\n";
$message .= "==============================================\n";
$message .= "CÓDIGO DE REGISTRO : {$receiptId}\n";
$message .= "FECHA Y HORA       : {$timestamp}\n";
$message .= "ORGANIZA           : Área de Diseño e Industria Digital - INACAP\n\n";
$message .= "DIRECTOR / RESPONSABLE:\n";
$message .= "- Nombre      : {$values['directorName']}\n";
$message .= "- Correo      : {$values['directorEmail']}\n";
$message .= "- Teléfono    : {$values['directorPhone']}\n";
$message .= "- Institución : {$values['institution']}\n\n";
$message .= "CORTOMETRAJE ANIMADO:\n";
$message .= "- Título      : {$values['filmTitle']}\n";
$message .= "- Duración    : {$values['filmDuration']}\n";
$message .= "- Técnica     : {$values['filmTechnique']}\n";
$message .= "- Identidad   : {$hasLocalIdentity}\n\n";
$message .= "SINOPSIS:\n\"{$values['filmSynopsis']}\"\n\n";
$message .= "ENLACE CARPETA NUBE:\n{$values['cloudLink']}\n\n";
$message .= "DECLARACIÓN AUTORAL:\n";
$message .= "[" . ($checkAiRule === 'on' ? 'X' : ' ') . "] Sin uso de IA generativa total (Carpeta Making Of incluida).\n";
$message .= "[" . ($checkRights === 'on' ? 'X' : ' ') . "] Aceptación de Bases FAIN 2026.\n";
$message .= "==============================================\n";

$headers = [];
$headers[] = 'From: FAIN 2026 <no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>';
$headers[] = 'Reply-To: ' . $values['directorEmail'];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

$sent = mail($to, $subject, $message, implode("\r\n", $headers));

if (!$sent) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'No se pudo enviar el correo. Revisa la configuración de correo del servidor.'
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'receiptId' => $receiptId,
    'timestamp' => $timestamp,
    'email' => $to,
    'message' => 'Postulación enviada correctamente.'
]);
