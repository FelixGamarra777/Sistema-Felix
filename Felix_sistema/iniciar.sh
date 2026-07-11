#!/bin/bash
# ============================================================
# iniciar.sh — Levanta el Sistema Felix de forma automática
#
# Uso:   ./iniciar.sh          (arranca todo y abre el navegador)
#        ./iniciar.sh stop     (detiene el servidor PHP y MySQL)
#
# Requisitos: Homebrew con php y mysql instalados
#   brew install php mysql
# ============================================================

set -e

PUERTO=8000
DB="cyber_compunet_db"
DIR="$(cd "$(dirname "$0")" && pwd)"
URL="http://localhost:$PUERTO/login.php"

# --- Modo stop -------------------------------------------------
if [ "$1" = "stop" ]; then
    echo "🛑 Deteniendo servidor PHP en puerto $PUERTO..."
    lsof -ti tcp:$PUERTO | xargs kill 2>/dev/null || echo "   (no había servidor corriendo)"
    echo "🛑 Deteniendo MySQL..."
    brew services stop mysql
    echo "✅ Todo detenido."
    exit 0
fi

# --- 1. MySQL ---------------------------------------------------
echo "🐬 Verificando MySQL..."
if ! mysqladmin ping -uroot --silent 2>/dev/null; then
    echo "   Arrancando MySQL con brew services..."
    brew services start mysql >/dev/null
    for i in {1..30}; do
        mysqladmin ping -uroot --silent 2>/dev/null && break
        sleep 1
    done
fi
if ! mysqladmin ping -uroot --silent 2>/dev/null; then
    echo "❌ MySQL no respondió. Revisa: brew services list"
    exit 1
fi
echo "   MySQL OK."

# --- 2. Base de datos -------------------------------------------
# NOTA: conexion.php intenta auto-crear la base, pero mysqli_connect
# (línea 10) corre antes y falla si la base no existe todavía.
# Por eso la creamos aquí primero.
echo "🗄️  Verificando base de datos '$DB'..."
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# Tabla usuarios (no la crea el instalador automático)
mysql -uroot "$DB" -e "CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
echo "   Base de datos OK."

# --- 3. Servidor PHP --------------------------------------------
echo "🐘 Levantando servidor PHP en puerto $PUERTO..."
lsof -ti tcp:$PUERTO | xargs kill 2>/dev/null || true
sleep 1
nohup php -S localhost:$PUERTO -t "$DIR" > "$DIR/.servidor.log" 2>&1 &
sleep 1
if ! curl -s -o /dev/null "http://localhost:$PUERTO/login.php"; then
    echo "❌ El servidor PHP no respondió. Revisa $DIR/.servidor.log"
    exit 1
fi

# --- 4. Usuario admin (solo si no existe) ------------------------
EXISTE=$(mysql -uroot "$DB" -N -e "SELECT COUNT(*) FROM usuarios WHERE usuario='admin';")
if [ "$EXISTE" = "0" ]; then
    echo "👤 Creando usuario admin (contraseña: mi_clave_secreta)..."
    curl -s "http://localhost:$PUERTO/crear_admin.php" >/dev/null
fi

# --- 5. Listo ----------------------------------------------------
echo ""
echo "✅ Sistema Felix corriendo en: $URL"
echo "   Usuario: admin   Contraseña: mi_clave_secreta"
echo "   Log del servidor: $DIR/.servidor.log"
echo "   Para detener todo: ./iniciar.sh stop"
open "$URL"
