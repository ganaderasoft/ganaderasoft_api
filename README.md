# GanaderaSoft API

**Sistema de Gestión Ganadera - API REST Core**

GanaderaSoft API es una aplicación REST API desarrollada en Laravel 10.x para la gestión integral de operaciones ganaderas, enfocada principalmente en el manejo de ganado búfalo y otras especies pecuarias.

## 📋 Descripción general

Esta API proporciona endpoints para la gestión completa de:
- Propietarios y fincas ganaderas
- Inventario y registro de animales
- Control de peso y medidas corporales
- Gestión de lactancia y producción lechera
- Estados de salud y seguimiento veterinario
- Configuraciones del sistema ganadero

## 1. ⚒️ Stack tecnológico

| Componente | Tecnología | Versión | Propósito |
| :--- | :--- | :--- | :--- |
| **Backend framework** | Laravel | 10.x | Lógica de negocio y API REST. |
| **Lenguaje servidor** | PHP | 8.1+ | Motor de ejecución del backend. |
| **Gestor del servidor** | Composer | 2.x | Gestor de dependencias para PHP. |
| **Base de datos** | MySQL | 8.0+ | Almacenamiento de datos relacional. |
| **Autenticación** | Sanctum | 3.2 | Gestión de tokens API. |
| **Cliente HTTP** | Guzzle | 7.2 | Peticiones HTTP. |
| **Testing** | PHPUnit | 10.1 | Pruebas unitarias. |
| **Servidor web** | Nginx | 1.18+ | Servidor web y proxy inverso (Producción). |
| **Entorno local** | Docker | - | Virtualización de servicios. |
| **CI/CD** | GitHub Actions | - | Automatización del despliegue a VPS. |

## 2. 📂 Infraestructura y arquitectura

### 2.1 Arquitectura de software
El proyecto del backend sigue la estructura de una API RESTful estándar de Laravel:

```text
/backend
├── app/                    # Lógica de la aplicación
│   ├── Console/            # Comandos de consola
│   ├── Exceptions/         # Manejadores de excepciones
│   ├── Http/               # Controladores y middleware
│   │   ├── Controllers/Api/# Controladores de la API
│   │   └── Middleware/     # Filtros de peticiones HTTP
│   ├── Models/             # Modelos Eloquent
│   └── Providers/          # Proveedores de servicios
├── bootstrap/              # Arranque del framework
├── config/                 # Configuraciones
├── database/               # Base de datos
│   ├── factories/          # Generadores de datos de prueba
│   ├── migrations/         # Esquema de tablas
│   └── seeders/            # Población inicial de datos
├── docs/                   # Documentación y colecciones Postman
├── openapi/                # Especificación Swagger/OpenAPI
├── public/                 # Punto de entrada público (index.php)
├── resources/              # Archivos de recursos
│   └── datos-constantes/   # Archivos JSON con configuraciones
├── routes/                 # Rutas de la API (api.php)
├── storage/                # Archivos locales y logs
└── tests/                  # Pruebas automatizadas (Feature, Unit)
```

### 2.2 Infraestructura de servidores

El proyecto maneja dos entornos con comportamientos diferentes:

1. Entorno local (Dockerizado para Desarrollo)
   - Orquestación: Usa `docker-compose.yml` montando volúmenes en tiempo real.
   - Servidor web: Servidor local de Artisan o Nginx interno.
   - Base de datos: Contenedor **MySQL**.
2. Entorno producción (Dockerizado Optimizado)
   - Orquestación: Usa `docker-compose.prod.yml` con imágenes compiladas independientemente.
   - Proxy externo: **Nginx** nativo en el servidor VPS (Reverse Proxy hacia Docker).
   - Procesador PHP: **PHP-FPM 8.2** ejecutándose internamente en el contenedor.
   - Base de datos: Servidor **MySQL** externo a la red de los contenedores web.

## 🚀 Pasos para desarrollo local

> [!IMPORTANT]
> Para el entorno de desarrollo local, es indispensable el uso de **Docker Compose** para la orquestación de los servicios.
> En caso de usar Windows, para garantizar la compatibilidad de los volúmenes y el rendimiento de los contenedores, es obligatorio ejecutar este proyecto utilizando **WSL2** (Windows Subsystem for Linux) integrado con **Docker Desktop**. Evite ejecutar los comandos directamente sobre PowerShell o CMD si no es a través de una terminal de WSL.

### 1. Estructura de archivos
Para comenzar, debe configurar la siguiente estructura de directorios en su entorno local dentro de una carpeta raíz (por ejemplo, `ganaderasoft/`):

```text
ganaderasoft/
├── backend/                  # Repositorio del API (Laravel)
├── frontend/                 # Repositorio de la interfaz (Laravel + Vue/Blade)
├── docker-compose.yml        # Orquestador de servicios (Desarrollo)
└── docker-compose.prod.yml   # Orquestador de servicios (Producción)
```
> [!NOTE]
> Tanto el código del `backend` como del `frontend` corresponden a sub-proyectos. Debe clonarlos o mantenerlos dentro de la carpeta principal para que el orquestador pueda localizar los archivos de configuración y los Dockerfiles.

### 2. Configuración de variables de entorno

Debe solicitar al equipo de desarrollo los archivos `.env` correspondientes al entorno de desarrollo.
- El archivo `.env` del backend debe colocarse en `ganaderasoft/backend/.env`.
- El archivo `.env` del frontend debe colocarse en `ganaderasoft/frontend/.env`.

Alternativamente, puede copiar los archivos de ejemplo si están disponibles (`cp .env.dev .env`).

### 3. Orquestación con docker

En la raíz de la carpeta `ganaderasoft/`, asegúrese de tener (o crear) un archivo llamado `docker-compose.yml` preconfigurado que orquestará los servicios de desarrollo:

<details>
<summary><b>Ver contenido de <code>docker-compose.yml</code> (Desarrollo)</b></summary>

```yaml
services:
  # Base de datos compartida (usada principalmente en desarrollo)
  db:
    image: mysql:8.0
    container_name: ganaderasoft-db
    restart: unless-stopped
    env_file:
      - ./backend/.env
    ports:
      - "3306:3306"
    volumes:
      # Persistencia de datos de MySQL
      - db_data:/var/lib/mysql
    healthcheck:
      # Verifica que MySQL esté listo antes de arrancar el backend
      test: [ "CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-proot_password" ]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s
    networks:
      - ganaderasoft-network

  # Backend (API Laravel)
  ganaderasoft-backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: ganaderasoft-backend
    restart: unless-stopped
    ports:
      - "8001:80"
    env_file:
      - ./backend/.env
    depends_on:
      db:
        condition: service_healthy
    networks:
      - ganaderasoft-network
    volumes:
      # Monta el código local para desarrollo en tiempo real
      - ./backend:/var/www/html
      # Ignora la carpeta local vendor (se instala dentro del contenedor en el entrypoint)
      - /var/www/html/vendor

  # Frontend (Laravel + Vue/Blade con Vite)
  ganaderasoft-frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: ganaderasoft-frontend
    restart: unless-stopped
    ports:
      - "8000:80"
      - "5173:5173" # Puerto para Vite (HMR)
    env_file:
      - ./frontend/.env
    depends_on:
      - ganaderasoft-backend
    networks:
      - ganaderasoft-network
    volumes:
      # Monta el código local para desarrollo en tiempo real
      - ./frontend:/var/www/html
      # Ignora las carpetas locales para evitar conflictos de sistema operativo
      - /var/www/html/vendor
      - /var/www/html/node_modules

volumes:
  db_data:

networks:
  ganaderasoft-network:
    driver: bridge
```
</details>

Y de igual forma, para producción, asegúrese de tener el archivo `docker-compose.prod.yml`:

<details>
<summary><b>Ver contenido de <code>docker-compose.prod.yml</code> (Producción)</b></summary>

```yaml
services:
  # Backend de Producción
  ganaderasoft-backend:
    build:
      context: ./backend
      # Usa el Dockerfile optimizado sin dependencias de desarrollo
      dockerfile: Dockerfile.prod
    container_name: ganaderasoft-backend-prod
    restart: always
    ports:
      - "127.0.0.1:8001:80"
    extra_hosts:
      - "host.docker.internal:host-gateway"
    env_file:
      - ./backend/.env
    volumes:
      # Persistencia de imágenes y archivos subidos (fotos, avatares, documentos)
      - ./backend/storage/app/public:/var/www/html/storage/app/public
    networks:
      - ganaderasoft-network

  # Frontend de Producción
  ganaderasoft-frontend:
    build:
      context: ./frontend
      # Usa el Dockerfile que compila Vite internamente
      dockerfile: Dockerfile.prod
    container_name: ganaderasoft-frontend-prod
    restart: always
    ports:
      - "127.0.0.1:8000:80"
    env_file:
      - ./frontend/.env
    depends_on:
      - ganaderasoft-backend
    networks:
      - ganaderasoft-network

networks:
  # Usa la misma red para que puedan conectarse a la BD de desarrollo si es necesario
  ganaderasoft-network:
    driver: bridge
```
</details>

### 4. Configuración de la base de datos
Para el entorno de desarrollo, el contenedor `ganaderasoft-db` de MySQL se encargará de proveer la base de datos con los parámetros configurados en el archivo `.env`:

| Variable / Parámetro | Valor por defecto (.env.dev) | Descripción |
| :--- | :--- | :--- |
| **DB_CONNECTION** | `mysql` | Motor de base de datos relacional. |
| **DB_HOST** | `ganaderasoft-db` | Host del servicio contenedor de base de datos. |
| **DB_PORT** | `3306` | Puerto de conexión a MySQL. |
| **DB_DATABASE** | `ganaderasoft` | Nombre de la base de datos principal. |
| **DB_USERNAME** | `ganaderasoft_user` | Usuario de base de datos. |
| **DB_PASSWORD** | *(Configurada en .env)* | Contraseña del usuario definida en el entorno. |

> [!IMPORTANT]
> **Importación de datos y migraciones**: 
> Si es la primera vez que levanta el proyecto o si la base de datos está vacía, es indispensable ingresar al contenedor del backend y ejecutar las migraciones junto con los seeders (datos semilla) iniciales:
> ```bash
> docker compose exec ganaderasoft-backend bash
> php artisan migrate --seed
> ```
> *(Alternativamente, si cuenta con un archivo SQL de respaldo como `bd_ganadera_soft.sql`, puede restaurarlo directamente en el gestor de base de datos de su preferencia utilizando las credenciales provistas).*

### 5. Ejecución del entorno con docker compose

Una vez configurada la estructura de archivos y las variables de entorno, inicie la orquestación de los contenedores ejecutando el siguiente comando en la raíz del proyecto (`/ganaderasoft`):

```bash
docker compose up --build
```
> [!TIP]
> Use el flag `--build` la primera vez o cuando realice cambios en los archivos `Dockerfile` o `entrypoint.sh` para asegurar que las imágenes se actualicen correctamente. Si desea ejecutar los contenedores en segundo plano y dejar la terminal libre, añada el flag `-d`.

### 6. Ejecución del entorno de producción (opcional)
Si desea probar cómo se comportará la aplicación en el servidor real (sin mapeo de volúmenes locales y con assets compilados), utilice el orquestador de producción.

> [!WARNING]
> **Requisito de base de datos**: 
> El archivo `docker-compose.prod.yml` **no incluye un contenedor de base de datos** por defecto. Por lo tanto, antes de levantarlo debe asegurarse de que la base de datos de desarrollo esté corriendo (`docker compose up -d db`), o bien, haber configurado las credenciales de un servidor MySQL externo en los archivos `.env`.

> [!IMPORTANT]
> **Variables de entorno para producción**: 
> Antes de construir las imágenes de producción, abra los archivos `.env` del Frontend y del Backend y asegúrese de configurar:
> - `APP_ENV=production`
> - `APP_DEBUG=false`
> 
> De lo contrario, Laravel seguirá intentando comportarse como si estuviera en desarrollo.

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

## 🌿 Política de ramas (Git Flow) y nomenclatura

El desarrollo de GanaderaSoft se rige bajo la metodología estándar de **Git Flow**. La estabilidad del código y los entornos se gestionan mediante las siguientes directrices obligatorias:

### Reglas de protección y flujo de integración

- 🚫 **Prohibición de push o merge directo**: Queda terminantemente prohibido realizar `git push` o fusiones directas sobre las ramas protegidas `main` y `develop`. Todo cambio debe integrarse a través de un **Pull Request (PR)**.
- 👥 **Revisión de código obligatoria para `main` (Peer Review)**: Para fusionar un Pull Request hacia la rama `main`, es **obligatorio que al menos un integrante del equipo distinto al autor** revise y apruebe formalmente el PR. La fusión a `main` activa de forma automática la tubería de **Despliegue Continuo (CD)** hacia el VPS de producción.
- 👤 **Integración en `develop` sin revisión externa**: Para integrar cambios hacia `develop`, **no es necesaria** la aprobación de un tercero; el propio desarrollador puede aprobar y fusionar su PR una vez concluidas y probadas sus tareas localmente.
- 🤖 **Eliminación automática de ramas en GitHub y limpieza local**: Al fusionar cualquier PR, **GitHub elimina automáticamente la rama remota de origen (*head branch*)**. El desarrollador no debe borrarla en GitHub manualmente; únicamente debe eliminar la rama de su entorno local y sincronizar referencias.

### Taxonomía y nomenclatura de ramas

| Prefijo / Rama | Origen | Destino | Revisión | Propósito y ejemplo representativo |
| :--- | :--- | :--- | :--- | :--- |
| `main` | — | — | **Protegida** | Rama de producción. Despliegue continuo (CD) automático. |
| `develop` | `main` | — | **Protegida** | Base de integración de desarrollo. Todo cambio converge aquí. |
| `feat/` o `feature/` | `develop` | `develop` | Autor (Local) | Nueva funcionalidad o módulo. Ej: `feat/modulo-pesaje`. |
| `fix/` o `bugfix/` | `develop` | `develop` | Autor (Local) | Corrección de fallos en desarrollo. Ej: `fix/calculo-indice-corporal`. |
| `hotfix/` | `main` | `main` y `develop` | **Obligatoria (Par)** | Parche crítico urgente en producción. Ej: `hotfix/error-login-sanctum`. |
| `docs/` | `develop` | `develop` | Autor (Local) | Documentación técnica y manuales. Ej: `docs/actualizar-api-endpoints`. |
| `chore/` | `develop` | `develop` | Autor (Local) | Mantenimiento, dependencias o configuración Docker. Ej: `chore/actualizar-composer`. |
| `refactor/` | `develop` | `develop` | Autor (Local) | Refactorización de código sin alterar lógica externa. Ej: `refactor/servicio-animales`. |
| `test/` | `develop` | `develop` | Autor (Local) | Adición o ajuste de pruebas unitarias/integración. Ej: `test/finca-policy-tests`. |
| `release/` | `develop` | `main` y `develop` | **Obligatoria (Par)** | Preparación de versión candidata a producción. Ej: `release/v1.0.0`. |

### Flujo de trabajo operativo para el desarrollador

```bash
# 1. Actualizar rama develop y crear la rama de trabajo según el tipo de cambio
git checkout develop
git pull origin develop
git checkout -b feat/nombre-tarea

# 2. Realizar cambios, probar localmente y subir al repositorio remoto
git add .
git commit -m "feat(animales): agregar filtro por especie y rebaño"
git push -u origin feat/nombre-tarea

# 3. Abrir el Pull Request en GitHub hacia develop (o hacia main si es hotfix/)
# - Si va a develop: el desarrollador aprueba y fusiona tras verificar pruebas.
# - Si va a main: solicita revisión y aprobación formal de un compañero de equipo.

# 4. Al fusionar, GitHub elimina la rama remota automáticamente.
# Limpie su copia local y sincronice las referencias podadas:
git checkout develop
git pull origin develop
git branch -d feat/nombre-tarea
git fetch -p
```

## 📚 Documentación adicional

- **Colección de Postman**: Disponible en `docs/postman-collections/`
- **Especificación OpenAPI**: Ver `openapi/ganaderasoft-api-v2.yaml`
- **Variables de entorno**: Copiar y configurar `.env` a partir de `.env.dev` o `.env.example`

## 🔧 Comandos útiles

Dado que el entorno está containerizado, todos los comandos de Artisan deben ejecutarse dentro del contenedor del backend (`ganaderasoft-backend`):

```bash
# Entrar a la consola del contenedor
docker compose exec ganaderasoft-backend bash

# Ejecutar pruebas (una vez dentro del contenedor)
php artisan test

# Limpiar caché
php artisan cache:clear

# Ver rutas disponibles
php artisan route:list
```

## 📝 Notas importantes

- Todos los endpoints de la API requieren autenticación excepto login/register
- Los datos de configuración se almacenan como archivos JSON estáticos
- La aplicación está optimizada para gestión de ganado búfalo pero es extensible
- Se incluyen relaciones complejas entre entidades para seguimiento completo
