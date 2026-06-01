# S.Taller - Sistema de Gestión de Taller Mecánico

S.Taller es una aplicación web intuitiva, segura y de alto rendimiento diseñada bajo el paradigma de programación orientada a objetos (POO) y una arquitectura limpia Modelo-Vista-Controlador (MVC) en PHP nativo. El sistema permite administrar de manera integrada clientes, vehículos, trabajadores, inventario de repuestos, servicios técnicos y órdenes de trabajo (OT) con generación de comprobantes y reportes en formato PDF.

---

## 🚀 Características Principales

*   **Arquitectura MVC y POO:** Código altamente desacoplado, modular y escalable.
*   **Gestión Integral de Órdenes de Trabajo (OT):**
    *   Registro del vehículo, propietario, mecánico asignado y fallas iniciales.
    *   Cálculo automático de subtotales, descuentos e impuestos (IVA) en tiempo real.
    *   Gestión de estados operativos (*Pendiente*, *En Progreso*, *Terminado*, *Cerrado*, *Anulado*).
*   **Facturación y Reportes PDF:**
    *   Generación en el acto de reportes de mano de obra y comprobantes de pago usando la librería FPDF integrada nativamente (libre de dependencias externas pesadas).
    *   Descarga directa e impresión directa optimizada mediante reglas CSS `@media print` que limpian elementos de la interfaz.
*   **Control de Inventario y Concurrencia:**
    *   Actualización atómica de stock de repuestos en base de datos.
    *   Sistema de control de concurrencia optimizado para prevenir la venta sin stock en operaciones simultáneas.
    *   Alertas visuales en el Dashboard para repuestos con stock por debajo del mínimo requerido.
*   **Seguridad Avanzada:**
    *   Protección contra ataques CSRF (Cross-Site Request Forgery) mediante tokens criptográficos aleatorios generados por sesión en todos los formularios.
    *   Prevención de inyección SQL (SQLi) mediante el uso estricto de sentencias preparadas con PDO.
    *   Sanitización bidireccional contra Cross-Site Scripting (XSS) mediante funciones específicas en las vistas.
    *   Control de acceso basado en roles (Administrador y Receptor) y regeneración de ID de sesión para mitigar la fijación de sesión.
*   **Diseño Responsivo (Mobile-Friendly):**
    *   Interfaz moderna adaptada a dispositivos móviles y tablets mediante Media Queries personalizadas en Vanilla CSS (sin frameworks pesados como Bootstrap o Tailwind).
    *   Grillas colapsables y tablas con desbordamiento horizontal suave para garantizar la operatividad desde celulares en el taller.

---

## 📂 Estructura del Proyecto

```text
taller/
│
├── app/                      # Núcleo de la aplicación MVC
│   ├── controllers/          # Controladores (Lógica de control)
│   ├── helpers/              # Clases auxiliares (Auth, PDF, etc.)
│   │   └── fpdf/             # Librería FPDF nativa
│   ├── models/               # Modelos (Interacción con base de datos)
│   └── views/                # Vistas (Interfaz de usuario HTML/CSS)
│
├── config/                   # Archivos de configuración
│   └── config.php            # Configuración general y carga de constantes
│
├── css/                      # Hojas de estilo globales (heredadas/auxiliares)
│
├── public/                   # Directorio raíz del servidor web (DocumentRoot)
│   ├── css/                  # Estilos CSS de producción (style.css)
│   ├── js/                   # Scripts Javascript y llamadas API
│   ├── invoices/             # Directorio de almacenamiento físico de PDFs generados
│   └── index.php             # Front Controller (Enrutador de la aplicación)
│
├── Dockerfile                # Configuración de Docker para Render
├── .dockerignore             # Exclusiones de archivos para Docker
├── .env.example              # Plantilla de variables de entorno
├── database.sql              # Estructura limpia de la Base de Datos
├── seed.php                  # Script CLI/Navegador para sembrado de datos
├── seed_data.sql             # Sentencias SQL para el sembrado inicial
└── README.md                 # Documentación del proyecto
```

---

## 🛠️ Instalación y Configuración Local

### Requisitos Previos

*   Servidor web con soporte de PHP 8.1 o superior (ej. **XAMPP**, Laragon, etc.)
*   Servidor de Base de Datos MySQL / MariaDB.
*   Extensión `PDO MySQL` y `GD` habilitadas en PHP.

### Paso 1: Clonar el Repositorio

Clona el proyecto dentro de la carpeta pública de tu servidor (ej. `C:/xampp/htdocs/`):

```bash
git clone https://github.com/JoseMPB13/S.Taller.git taller
```

### Paso 2: Configurar las Variables de Entorno

Copia el archivo `.env.example` y renombralo a `.env` en la raíz del proyecto:

```bash
cp .env.example .env
```

Edita `.env` con las credenciales de tu base de datos y la URL base:

```ini
DB_HOST=localhost
DB_NAME=taller_mecanico
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# URL base del proyecto en tu entorno local (importante para las rutas amigables)
BASE_URL=http://localhost/taller
```

### Paso 3: Crear y Sembrar la Base de Datos

Puedes inicializar la base de datos de dos formas:

#### Opción A: Mediante el Sembrador Automático (Recomendado)
Asegúrate de que el servidor MySQL esté corriendo, crea una base de datos vacía llamada `taller_mecanico` y ejecuta en tu terminal:

```bash
php seed.php
```

*Nota: También puedes acceder a este script a través del navegador ingresando a `http://localhost/taller/seed.php`.*

#### Opción B: Importación Manual
1. Crea la base de datos `taller_mecanico`.
2. Importa el archivo `database.sql` para crear la estructura de tablas.
3. Importa el archivo `seed_data.sql` para insertar los datos iniciales de prueba y los roles por defecto.

### Paso 4: Credenciales de Acceso por Defecto

Una vez sembrados los datos, puedes ingresar al sistema con las siguientes cuentas de prueba:

*   **Administrador:**
    *   **Usuario/Correo:** `admin@taller.com`
    *   **Contraseña:** `admin123`
*   **Receptor:**
    *   **Usuario/Correo:** `receptor@taller.com`
    *   **Contraseña:** `receptor123`

---

## 🐳 Despliegue en Producción (Docker / Render)

Este proyecto está completamente dockerizado y optimizado para funcionar en plataformas PaaS como **Render**.

### Variables de Entorno en Producción

En el panel de control de Render (o tu proveedor cloud), configura las siguientes variables de entorno secretas:

| Variable | Descripción | Valor de Ejemplo |
| :--- | :--- | :--- |
| `DB_HOST` | Host de la base de datos externa o managed service | `dpg-xxxxxx-mysql.render.com` |
| `DB_NAME` | Nombre de la base de datos | `taller_mecanico_prod` |
| `DB_USER` | Usuario de conexión MySQL | `taller_admin` |
| `DB_PASS` | Contraseña de conexión MySQL | `mypassword123` |
| `DB_CHARSET`| Codificación de caracteres | `utf8mb4` |

### Construcción Local de Docker (Opcional)

Si deseas probar el contenedor en tu máquina local:

```bash
# Construir la imagen
docker build -t s-taller .

# Ejecutar el contenedor mapeando el puerto 80
docker run -d -p 8080:80 --name taller-app s-taller
```

Ingresa a la app desde el navegador en `http://localhost:8080`.

---

## 🔒 Contribución y Seguridad

Para mantener la seguridad del sistema en producción:
1. Asegúrate de desactivar la visualización de errores (`display_errors = Off`) en tu archivo `php.ini` de producción.
2. Nunca subas el archivo `.env` o la carpeta `.git` al contenedor Docker (gestionado automáticamente por el archivo `.dockerignore`).
3. Cambia las contraseñas de las cuentas por defecto inmediatamente después de realizar la primera instalación en producción.
