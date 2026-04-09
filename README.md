# Biblioteca API

API para la gestion de biblioteca desarrollada con Laravel.

## Requisitos

- PHP 8.2 o superior
- Composer
- Node.js 18 o superior y npm
- MySQL o MariaDB
- Herd (flujo oficial del equipo para ejecutar el proyecto)

## Inicializacion del proyecto

1. Clonar el repositorio y entrar al proyecto.
2. Instalar dependencias de PHP:

```bash
composer install
```

3. Crear el archivo de entorno:

```bash
cp .env.example .env
```

4. Generar la clave de la aplicacion:

```bash
php artisan key:generate
```

5. Configurar la base de datos en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=biblioteca
DB_USERNAME=root
DB_PASSWORD=
```

6. Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

7. Instalar dependencias de frontend:

```bash
npm install
```

## Levantamiento del proyecto

### Importante

Este proyecto lo levantamos desde Herd.

- No usar `php artisan serve` como flujo principal del equipo.
- Ejecutar el proyecto desde Herd y abrir la URL que Herd asigne al sitio.

Para compilar assets durante desarrollo:

```bash
npm run dev
```

Para build de produccion:

```bash
npm run build
```

## Pruebas

Ejecutar pruebas con:

```bash
php artisan test
```

## Levantar documentacion de Stoplight

La especificacion OpenAPI del proyecto esta en el archivo `openapi.stoplight.yaml`.

La forma en que nosotros la levantamos es desde el import en la web de Stoplight.

1. Entrar a https://stoplight.io/.
2. Iniciar sesion en el workspace del equipo.
3. Crear o abrir el proyecto de documentacion.
4. Usar la opcion de importacion de OpenAPI y seleccionar `openapi.stoplight.yaml`.
5. Guardar los cambios para visualizar la documentacion publicada en Stoplight.

## Colecciones Postman

En la raiz del proyecto estan:

- `Biblioteca-API-Collection.postman_collection.json`
- `Biblioteca-Environment.postman_environment.json`

Importa ambos archivos en Postman para probar los endpoints.
