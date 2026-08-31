# Guía de Uso y Servidor - Sistema de Gestión de Hábitos

Esta guía te ayudará a encender el entorno de desarrollo y probar todos los flujos de negocio y calidad (QA) del sistema de hábitos de manera rápida y sencilla.

---

## 1. Tutorial para Encender el Servidor

Sigue estos pasos en tu terminal (PowerShell o Git Bash) para iniciar el servidor de backend y compilar los estilos interactivos del frontend.

### Paso 1: Levantar el Servidor de Backend (Laravel)
En la carpeta raíz del proyecto, ejecuta el comando de Artisan para iniciar el servidor web local de PHP:
```bash
php artisan serve
```
*Esto iniciará el servidor en [http://127.0.0.1:8000](http://127.0.0.1:8000).*

### Paso 2: Levantar el Servidor de Frontend (Vite)
Abre otra pestaña o ventana de la terminal (dejando la anterior corriendo) y ejecuta Vite para compilar y servir los scripts de JavaScript (AlpineJS) y las clases de Tailwind CSS v4 en tiempo real:
```bash
npm run dev
```

### Paso 3: Inicializar/Actualizar la Base de Datos (Opcional)
Si quieres resetear la base de datos sqlite y cargar los datos semilla con los usuarios de prueba, las categorías y el historial de hábitos inicial:
```bash
php artisan migrate:fresh --seed
```

### Paso 4: Ejecutar los Tests de QA
Si deseas verificar que todo el backend y los controladores sigan funcionando de forma óptima (34 tests aprobados):
```bash
php artisan test
```

---

## 2. Tutorial del Flujo de la Aplicación

Una vez que tengas ambos servidores corriendo (`php artisan serve` y `npm run dev`), abre tu navegador e ingresa a [http://127.0.0.1:8000](http://127.0.0.1:8000).

### Flujo A: Probar con Usuarios Semilla (Recomendado para Demo)

#### 1. Inicio de Sesión
Haz clic en **Log in** en la esquina superior derecha e ingresa con alguno de estos dos perfiles precargados:
* **Usuario Gratuito (Sujeto a restricciones):**
  - **Email:** `gratis@example.com`
  - **Contraseña:** `password`
* **Usuario Premium (Acceso completo):**
  - **Email:** `premium@example.com`
  - **Contraseña:** `password`

#### 2. Tracking de Hábitos en el Dashboard
* Al ingresar a `/dashboard`, verás los hábitos programados para hoy (ej. *Caminar 30 min* y *Estudiar Laravel*).
* Haz clic en **✓ Completado**, **× Fallado** o **– Omitido** en cualquiera de las tarjetas de hábito. Verás cómo los botones cambian de color de forma inmediata gracias al guardado asíncrono (AJAX).
* Haz clic en el botón con el ícono de lápiz (**✍**) al lado del grupo de botones para abrir una tarjeta emergente donde podrás escribir un comentario del día y guardarlo en esa ocurrencia.

#### 3. Simulación de Conversión Freemium (Límite de 5 Hábitos Activos)
* Inicia sesión con el **Usuario Gratuito** (`gratis@example.com`).
* Ve al menú **Nuevo hábito** en la barra lateral.
* Como el usuario gratuito de semilla ya tiene hábitos creados y activos, el sistema detectará que ha alcanzado el límite de 5 hábitos activos de su plan. El formulario de creación se bloqueará dinámicamente y se mostrará un banner de **Upgrade Premium** de USD 4/mes.

#### 4. Activar/Desactivar Plan Premium
* Ve al menú **Premium** en la barra lateral.
* Verás una tabla comparativa de planes y un botón grande en la parte superior:
  - Si eres usuario gratuito, haz clic en **◆ Activar 14 días gratis de Premium**. El sistema simulará la compra sumando 14 días al campo `plan_expires_at` del usuario en la base de datos.
  - Si eres premium, haz clic en **Cancelar prueba (Downgrade a Gratis)** para revertir el estado.
* *Prueba de validación:* Activa el plan Premium, regresa a **Nuevo hábito** y verás cómo el formulario se desbloquea por completo permitiéndote crear hábitos sin límites.

#### 5. Estadísticas y Consistencia de Racha
* Ve al menú **Estadísticas** en la barra lateral.
* Selecciona un hábito de la lista de la izquierda.
* Verás las métricas calculadas en el momento: **Racha Actual** (consecutivos hechos), **Racha Máxima** e **Índice de Consistencia**.
* Analiza el **Historial de Cumplimiento** en formato de calendario:
  - Si eres **Usuario Gratuito**, el calendario estará estrictamente limitado a los **últimos 7 días** corridos.
  - Si simulas el **Plan Premium** (Paso 4), vuelve a recargar estadísticas y verás que ahora el calendario muestra el historial completo desde la fecha de inicio del hábito.

---

### Flujo B: Registro de Nuevo Usuario (Timezone Auto-detect)

1. En la página de bienvenida, haz clic en **Register** en la esquina superior derecha.
2. Completa tus datos. Verás el campo **Zona Horaria**:
   - Gracias a un script de geolocalización horaria por navegador, tu zona horaria local (ej. `America/Argentina/Buenos_Aires`) se pre-seleccionará automáticamente en el selector.
   - Puedes cambiarla si lo deseas para probar el cronjob con husos horarios diferentes.
3. Regístrate e inicia el flujo desde cero con una cuenta limpia.

---

### Flujo C: Comando de Cierre Diario (Cierre del Cronjob)

Para simular de manera manual cómo actúa el cronjob programado a medianoche de cada usuario:
1. Crea un hábito para hoy y déjalo sin marcar en el dashboard.
2. Abre tu terminal y ejecuta el comando Artisan de cierre de día:
   ```bash
   php artisan habits:close-day
   ```
3. Si en tu zona horaria local es la hora de la medianoche (`00:00 - 00:59`), el comando marcará el hábito pendiente de ayer como **Omitido** por el sistema y reseteará tu racha actual a 0. Si no es tu medianoche, el comando informará que no procesó hábitos para tu zona horaria (lo cual valida el aislamiento de timezones).

---

## 3. Testing Embebido & Despliegue en la Nube (CI/CD)

El proyecto cuenta con un sistema de testing automatizado embebido en el flujo de desarrollo y repositorio:

### A. Pipeline Automatizado de GitHub Actions (CI)
Al hacer `git push` a tu repositorio en las ramas `main`, `master` o `develop`, se dispara automáticamente el flujo de **GitHub Actions** configurado en `.github/workflows/tests.yml`:
1. Levanta un entorno Ubuntu con PHP 8.3 y SQLite.
2. Instala dependencias con Composer y compila los assets frontend con Vite/Tailwind v4.
3. Ejecuta los **34 tests automatizados** y genera el reporte de éxito en la pestaña **Actions** de GitHub.

### B. Despliegue en Servidores de Desarrollo
Para desplegar la aplicación a un servidor de desarrollo accesible por internet:

#### En Railway.app:
1. Entra a [Railway.app](https://railway.app) y haz clic en **New Project → Deploy from GitHub repo**.
2. Selecciona tu repositorio `habitosapp`.
3. En la pestaña **Variables**, añade las siguientes variables de entorno:
   - `APP_NAME=Habitos`
   - `APP_ENV=production`
   - `APP_KEY=` *(El valor de tu APP_KEY del archivo .env local)*
   - `APP_DEBUG=false`
   - `APP_URL=` *(La URL pública que te asigne Railway)*
   - `DB_CONNECTION=sqlite`
4. En **Settings → Build & Start Command**, Railway detectará Laravel automáticamente mediante Nixpacks y ejecutará:
   - Build: `composer install --no-dev && npm install && npm run build`
   - Start: `php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT`
