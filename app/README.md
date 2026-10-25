# Donwok API

Base de Symfony 7.4 preparada para ejecutarse con Docker:

- PHP 8.3 + PHP-FPM
- Nginx
- MySQL 8.4
- Redis 7
- MinIO
- Mercure
- Symfony Messenger worker

## Arranque

Configura las variables de Symfony en `app/.env.local`. Ese archivo no se
inyecta desde Docker: se carga directamente por Symfony desde el volumen del
proyecto. Como mínimo debe contener `APP_SECRET`, `DATABASE_URL`, `REDIS_URL`,
`MERCURE_URL`, `MERCURE_PUBLIC_URL` y `MERCURE_JWT_SECRET`.

```bash
docker compose up -d --build
```

La API queda disponible en `http://localhost` y Mercure se expone a través del
proxy en `http://localhost/.well-known/mercure`.

Comprobación rápida:

```bash
curl http://localhost/api/health
```

Para entrar al contenedor PHP:

```bash
docker compose exec app sh
```

Las credenciales de desarrollo están en `.env`; para un entorno real deben
sobrescribirse en `.env.local` o mediante variables del entorno.

Los datos persistentes de MySQL y Redis se guardan en las carpetas
`mysql_data/` y `redis_data/` del directorio raíz. Estas carpetas quedan fuera
del contexto de build mediante `.dockerignore`.

MinIO guarda sus datos en `minio_data/` y está disponible internamente como
`http://minio:9000` para la aplicación. Su consola administrativa queda
disponible mediante el proxy en `http://localhost/minios-admin`.

Los límites de memoria de todos los contenedores suman 992 MiB. No se han
limitado los procesadores; Docker puede usar todos los CPU disponibles.

El worker procesa mensajes asíncronos mediante Redis y se ejecuta con:

```bash
docker compose logs -f worker
```
