# CryptoDungeon

CryptoDungeon es una aplicación web educativa que convierte el aprendizaje de la criptografía en un escape room. Los alumnos exploran una mazmorra 2D y resuelven mensajes cifrados para desbloquear habitaciones y avanzar hasta la salida.

Está pensada para utilizarse en el aula: una persona crea la sala y organiza la partida, y los demás participan individualmente o por equipos. Todos acceden desde el navegador, sin registro ni inicio de sesión.

## Cómo se juega

1. El anfitrión crea una sala, elige la dificultad y establece su duración. En modo por equipos, crea los grupos.
2. Los alumnos se unen con un código de acceso de seis cifras y un nombre o alias.
3. Los participantes esperan en un lobby hasta que el anfitrión inicia la partida.
4. Cada equipo resuelve los acertijos de las habitaciones y avanza por su propio recorrido.
5. Las pistas ayudan a superar los desafíos a cambio de puntos. Al terminar, se muestran los resultados.

La experiencia está diseñada alrededor de cinco habitaciones conectadas por un recorrido válido. Los desafíos utilizan cifrados clásicos como **César, Atbash y Vigenère**, con frases y parámetros variables entre partidas.

## Objetivo educativo

El juego busca que los alumnos comprendan cómo funcionan los cifrados clásicos, identifiquen patrones y practiquen el razonamiento lógico. El formato por equipos permite trabajar también la comunicación y la resolución colaborativa de problemas.

El anfitrión dispone de una vista de seguimiento para acompañar a los grupos y detectar dónde necesitan ayuda.

El control de la sala se conserva en la sesión del navegador de quien la crea. El código permite unirse como participante, pero no gestionar los grupos. Para volver a administrar una sala, hay que utilizar el mismo navegador y conservar sus cookies.

## Estado del proyecto

Actualmente están implementados la creación de salas sin cuenta, la configuración de dificultad y duración, la gestión de grupos por el creador, el acceso de alumnos con código y los lobbies con actualización periódica.

La exploración de la mazmorra, los acertijos, las pistas, la puntuación y los resultados están en desarrollo. La descripción del juego recoge la experiencia prevista.

## Tecnologías

| Área | Tecnología |
| --- | --- |
| Backend | PHP y Laravel |
| Interfaz | Blade, Livewire y Alpine.js |
| Estilos y mapa 2D | Tailwind CSS y CSS Grid |
| Base de datos | SQLite, con configuración disponible para MariaDB |
| Seguimiento de sesiones | Polling con Livewire |
| Entorno local | Laravel Herd |

La aplicación concentra las reglas del juego en el servidor: la validación de respuestas, el progreso y las puntuaciones se calculan en el backend. Las soluciones de los acertijos deben permanecer fuera de los datos enviados al navegador.

## Desarrollo local

El proyecto utiliza Composer para las dependencias PHP y npm para los recursos frontend. Las versiones de dependencias están fijadas en `composer.lock` y `package-lock.json`; la versión de Node está indicada en `.nvmrc`.

Con el entorno configurado en Herd, la aplicación se puede abrir en [cryptodungeon.test](https://cryptodungeon.test). Para trabajar en la interfaz con recarga automática:

```sh
npm run dev
```

Después de actualizar el código, aplica las migraciones pendientes antes de utilizar la aplicación. Esto actualiza el esquema de la base de datos conservando los registros existentes:

```sh
php artisan migrate
```

Para ejecutar las pruebas y generar los recursos de producción:

```sh
php artisan test
npm run build
```

## Publicación

El proyecto incluye una adaptación para Vercel con PHP y una base PostgreSQL externa en Neon. El entorno local sigue utilizando Herd y SQLite. La configuración del despliegue, las variables de entorno y las comprobaciones están explicadas en la [guía de Vercel y Neon](docs/vercel.md).
