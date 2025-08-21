# Instrucciones de instalación

A continuación se describen los pasos para instalar el proyecto en local

## Docker

* Copiar el archivo .env.example

```sh
cp .env.example .env
```

Asegurese de tener docker desktop instalado.

* **Iniciar contenedores y servicios:**
```sh
docker compose up -d
```

* **Detener contenedores y servicios:**
```sh
docker compose down

# Si desea eliminar volumenes y toda la información:
docker compose down -v
```

* Entrar al contenedor de la aplicación
```sh
docker exec -it laravel_app bash
```

* Instalar dependencias de composer
```sh
composer install
```

* Generar key
```sh
php artisan key:generate
```

* Actualizar conexión a base de datos en .env
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

* Correr migraciones y seeders
```sh
php artisan migrate --seed
```

* Ejecutar tests
```sh
php artisan test
```

Listo ahora puede cargar en un navegador.

http://localhost:8080


## Documentación del API de ordenes

[Ver Documentación](API_DOCUMENTATION.md)