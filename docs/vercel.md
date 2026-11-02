# Publicar CryptoDungeon en Vercel con Neon

La adaptación usa `vercel-php@0.9.0` (PHP 8.5), un runtime comunitario, y PostgreSQL de Neon para guardar salas, participantes, sesiones y caché. Vite genera los recursos estáticos. Herd y SQLite siguen siendo el entorno de desarrollo local.

Vercel Hobby y Neon Free permiten probar el proyecto dentro de sus límites de uso. El polling consume peticiones y actividad de base de datos; el coste cero no implica capacidad ilimitada. El runtime PHP es comunitario, sin soporte oficial de Vercel.

## 1. Crear la base en Neon

1. Crea una cuenta y un proyecto en el plan **Free** de Neon.
2. Elige una región cercana a donde ejecutarás la aplicación.
3. Abre **Connect** y copia la conexión **Pooled** de PostgreSQL. Conserva `sslmode=require` en la URL.
4. Guarda esa conexión como `DB_URL` en Vercel. No la añadas al repositorio ni la pegues en el chat.

Puedes usar la integración de Neon del Marketplace de Vercel. Si proporciona `DATABASE_URL`, también está admitida; `DB_URL` tiene prioridad cuando ambas existen.

La base de Neon es independiente de SQLite. No se sube ni se borra `database/database.sqlite`, y las salas locales no se copian automáticamente a producción.

## 2. Configurar el proyecto de Vercel

El archivo `vercel.json` contiene la configuración. Si habías seleccionado Vite, cambia el preset y elimina los overrides anteriores que entren en conflicto:

Mantén activada la opción **Automatically expose System Environment Variables** de Vercel: el hook utiliza `VERCEL=1` para distinguir el despliegue del entorno local.

| Ajuste | Valor |
| --- | --- |
| Root Directory | La raíz del proyecto, donde están `composer.json` y `vercel.json` |
| Framework Preset | **Other** |
| Node.js Version | Detectada mediante `engines.node` en `package.json` (**26.x**) |
| Install Command | `npm ci --ignore-scripts` |
| Build Command | `npm run build:vercel` |
| Output Directory | `dist` |

El build de Node compila Vite y copia únicamente los recursos públicos permitidos a `dist`. La función `api/index.php` atiende las rutas de Laravel, incluyendo las peticiones de Livewire y el polling. La salida estática no contiene PHP, archivos `.env`, bases SQLite ni enlaces al servidor Vite local.

No selecciones Node.js como framework: el preset es **Other**. El selector de versión de Node puede no aparecer en la pantalla de importación; `package.json` ya declara la versión. Si necesitas consultarlo después, está en los ajustes de build del proyecto.

El runtime PHP instala las dependencias de Composer y ejecuta el script `vercel` definido en `composer.json`. No uses `composer run setup` como comando de Vercel: ese comando está pensado para una instalación local.

## 3. Añadir las variables de producción

En **Settings → Environment Variables**, configura estas variables para **Production**. `.env.vercel.example` contiene una plantilla sin secretos.

| Variable | Valor |
| --- | --- |
| `APP_NAME` | `CryptoDungeon` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_LOCALE` | `es` |
| `APP_URL` | Opcional: se detecta automáticamente; omítela para usar el dominio de Vercel |
| `APP_KEY` | Una clave nueva de producción con prefijo `base64:` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | Conexión pooled de Neon con SSL |
| `DB_SSLMODE` | `require` |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `LOG_CHANNEL` | `stderr` |

Para obtener una clave nueva, ejecuta en tu terminal:

```sh
php artisan key:generate --show
```

La detección de URL usa `VERCEL_PROJECT_PRODUCTION_URL` en producción y `VERCEL_URL` en previews, añadiendo `https://`. En producción, si no existe el dominio estable, utiliza el del despliegue. No necesitas publicar primero y volver a editar `APP_URL`. Una `APP_URL` definida manualmente tiene prioridad sobre la detección automática.

`--show` muestra una clave nueva sin modificar la clave de tu `.env` local. Copia el resultado a `APP_KEY` en Vercel y consérvalo entre despliegues. Cambiarlo invalida las cookies existentes y puede hacer perder el acceso de los anfitriones a sus salas.

No añadas `SESSION_DOMAIN` salvo que tengas una necesidad concreta de compartir cookies entre subdominios. No publiques secretos con variables cuyo nombre empiece por `VITE_`.

## 4. Desplegar y aplicar las migraciones

Sube los cambios a GitHub manualmente y lanza el despliegue. El proyecto no realiza commits ni subidas automáticas.

El hook de Composer comprueba los requisitos de PHP, la configuración de producción y la presencia del manifiesto frontend. Después ejecuta el descubrimiento de paquetes y `migrate --force` contra Neon. Si faltan variables o fallan las migraciones, detiene el build. No genera claves nuevas ni reinicia la base de datos.

Las migraciones se ejecutan antes de activar el nuevo despliegue. Los futuros cambios de esquema deben ser compatibles con la versión que siga sirviendo tráfico durante ese intervalo.

No se ejecutan migraciones desde las peticiones de los alumnos ni desde el polling. Los archivos temporales de `/tmp` contienen vistas compiladas y manifiestos del framework; el estado de juego y las sesiones se guardan en Neon.

Las posiciones de los jugadores se guardan en `participants.current_room_id`. La migración añade un campo nullable y conserva las partidas existentes; su ubicación se inicializa al volver a entrar. El mapa del anfitrión utiliza el polling de tres segundos. El build también publica `public/audio` como archivos estáticos para la música ambiental, sin variables nuevas ni servicios externos.

## Preview y Production

Usa una rama/base de Neon distinta para las previews, con sus propias variables `DB_URL` y `APP_KEY`. Su URL se detecta automáticamente. **Cada build configurado ejecuta migraciones contra su conexión**: una preview no debe apuntar a la base de producción.

Si todavía no tienes una base para previews, configura solo Production. Los builds de preview sin las variables necesarias fallarán de forma explícita; no se cambiará a SQLite temporal para ocultar el problema.

## Comprobar el despliegue

1. Abre `/up` para comprobar que arranca PHP y Laravel.
2. Abre `/salas/crear`, crea una sala y añade dos grupos.
3. En una ventana privada, entra por `/entrar` con el código y selecciona un grupo.
4. Comprueba que el anfitrión ve al participante mediante polling.
5. Recarga ambas ventanas y confirma que conservan su identidad y permisos.
6. Comprueba que el participante recibe acceso denegado si intenta abrir el lobby de administración.

El control de una sala sigue asociado a la sesión del navegador del creador. Neon permite compartir esa sesión entre instancias de Vercel.

### Compatibilidad SNI del runtime PHP

El runtime comunitario puede utilizar una biblioteca libpq sin soporte SNI. En Vercel se utiliza un conector PostgreSQL que extrae el endpoint del host de Neon y lo envía explícitamente mediante `options='endpoint=ep-…'`, siguiendo la alternativa documentada por Neon. Para conexiones pooled, elimina `-pooler` únicamente del identificador del endpoint; conserva el host pooled.

No necesitas modificar `DB_URL` ni añadir otra variable. Se mantienen SSL y la contraseña original. El conector se registra solo en Vercel y conserva el comportamiento estándar para hosts PostgreSQL ajenos a Neon. Herd y SQLite no utilizan esta adaptación.

Referencia: https://neon.com/docs/connect/connection-errors#the-endpoint-id-is-not-specified

## Si falla la preparación de la base

El build comprueba primero la conexión con `SELECT 1` y después ejecuta las migraciones. Los logs indican la etapa y, en caso de error, un SQLSTATE y una orientación. Los fallos de conexión incluyen además el motivo del controlador PostgreSQL, ocultando los datos de conexión, contraseñas y detalles de consultas. Si falla una migración, se indica su archivo.

Comparte únicamente la línea que empieza por `[Vercel] Fallo en` para diagnosticar el problema. No actives `APP_DEBUG` ni reinicies la base para intentar resolverlo. En `DB_URL` pega solo la URL que empieza por `postgresql://`, sin el comando `psql` ni comillas envolventes.

## Verificación local

```sh
php artisan test
npm run build:vercel
```

Las pruebas de Vercel simulan el bootstrap, los directorios temporales, el proxy HTTPS, los permisos del anfitrión y los errores de configuración. Usan SQLite aislado; no sustituyen la prueba final de conexión con tu proyecto real de Neon ni un despliegue en Vercel.

Fuentes de referencia:

- Runtime PHP: https://github.com/vercel-community/php
- Configuración de Vercel: https://vercel.com/docs/project-configuration/vercel-json
- Limitaciones de SQLite: https://vercel.com/kb/guide/is-sqlite-supported-in-vercel
- Plan gratuito de Neon: https://neon.com/pricing
