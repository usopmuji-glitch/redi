<?php
define('DATA_FILE', 'xzw.json');

// Cargar datos desde JSON
function load_data() {
    if (!file_exists(DATA_FILE)) {
        return [];
    }
    $json = file_get_contents(DATA_FILE);
    return json_decode($json, true) ?? [];
}

// Guardar datos en JSON
function save_data($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// Obtener la IP real del usuario
function get_real_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'Desconocido';
    }
}

// Generar o recuperar un identificador único para el dispositivo
function get_device_id() {
    if (!isset($_COOKIE['device_id'])) {
        $deviceId = bin2hex(random_bytes(16));
        setcookie('device_id', $deviceId, time() + (86400 * 365), "/");
    } else {
        $deviceId = $_COOKIE['device_id'];
    }
    return $deviceId;
}

// Obtener la IP y ubicación del usuario
function get_user_info() {
    $ip = get_real_ip();
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $ipInfo = @json_decode(file_get_contents("https://ipinfo.io/{$ip}/json"), true) ?: [];
    } else {
        $ipInfo = [];
    }
    return [
        'ip' => $ip,
        'location' => isset($ipInfo['city']) && isset($ipInfo['country'])
            ? "{$ipInfo['city']}, {$ipInfo['country']}"
            : 'Ubicación desconocida'
    ];
}

// Función para asignar un color siguiendo el ciclo de cuatro colores predefinidos:
// #FF0000, #000000, #008000, #0000FF
// Solo se asigna color a usuarios con contenido en "submissions" y se asigna de forma fija.
function assign_cyclic_color($data) {
    $colors = ['#FF0000', '#000000', '#008000', '#0000FF'];
    $assignedCount = 0;
    // Contar cuántos usuarios ya tienen un color asignado y contenido en submissions
    foreach ($data as $user) {
        if (!empty($user['submissions']) && !empty($user['color'])) {
            $assignedCount++;
        }
    }
    return $colors[$assignedCount % count($colors)];
}

// Actualizar o registrar usuario por device_id, revisando además si el IP ya existe.
// Se asigna el campo "color" SOLO si el array "submissions" tiene contenido.
function update_user($ip, $location, $currentPage) {
    $data = load_data();
    $deviceId = get_device_id();

    // Buscar si ya existe un registro con el mismo IP
    $existingKey = null;
    foreach ($data as $key => $user) {
        if (isset($user['ip']) && $user['ip'] === $ip) {
            $existingKey = $key;
            break;
        }
    }

    if ($existingKey !== null) {
        // Actualizamos el registro existente
        $data[$existingKey]['location'] = $location;
        $data[$existingKey]['currentPage'] = $currentPage;
    } else {
        // Si no existe, verificamos si ya hay un registro para este device_id
        if (!isset($data[$deviceId])) {
            $newEntry = [
                'device_id'   => $deviceId,
                'ip'          => $ip,
                'location'    => $location,
                'currentPage' => $currentPage,
                'submissions' => [],
                'color'       => '',
                'state'       => 'active',  // Estado usado para otra funcionalidad
                'status'      => 'online'   // Campo para online/offline
            ];
            // Solo asignar color si hay contenido en submissions (aunque en un nuevo registro, normalmente estará vacío)
            $data[$deviceId] = $newEntry;
        } else {
            // Si ya existe un registro para este device_id, lo actualizamos
            $data[$deviceId]['ip'] = $ip;
            $data[$deviceId]['currentPage'] = $currentPage;
        }
    }

    save_data($data);
}

function delete_user($device_id) {
    $data = load_data();

    if (isset($data[$device_id])) {
        unset($data[$device_id]);
        save_data($data);
        return true;
    }

    return false;
}

// Establecer el estado del usuario por device_id (sin modificar, se usa para otro propósito)
function set_user_state($deviceId, $state) {
    $data = load_data();
    if (isset($data[$deviceId])) {
        $data[$deviceId]['state'] = $state;
        save_data($data);
    }
}

// Obtener el estado del usuario por device_id (sin modificar)
function get_user_state($deviceId) {
    $data = load_data();
    return isset($data[$deviceId]['state']) ? $data[$deviceId]['state'] : 'active';
}

// Actualización del status (online/offline) mediante "heartbeat" y unload
if (isset($_GET['action'])) {
    $deviceId = get_device_id();
    $data = load_data();
    if (isset($data[$deviceId])) {
        if ($_GET['action'] === 'offline') {
            $data[$deviceId]['status'] = 'offline';
        } elseif ($_GET['action'] === 'ping') {
            $data[$deviceId]['status'] = 'online';
            $data[$deviceId]['last_active'] = time();
        }
        save_data($data);
    }
    exit();
}
?>