# CryptoDungeon

CryptoDungeon es una aplicación web educativa que convierte el aprendizaje de la criptografía en un escape room. Los alumnos exploran una mazmorra 2D y resuelven mensajes cifrados para desbloquear habitaciones y avanzar hasta la salida.

Está pensada para utilizarse en el aula: el profesor organiza la partida y los alumnos participan individualmente o por equipos, desde el navegador y sin crear una cuenta.

## Cómo se juega

1. El profesor crea una sesión, elige la dificultad y establece su duración.
2. Los alumnos se unen con un código de acceso de seis cifras y un nombre o alias.
3. Los participantes esperan en un lobby hasta que el profesor inicia la partida.
4. Cada equipo resuelve los acertijos de las habitaciones y avanza por su propio recorrido.
5. Las pistas ayudan a superar los desafíos a cambio de puntos. Al terminar, se muestran los resultados.

La experiencia está diseñada alrededor de cinco habitaciones conectadas por un recorrido válido. Los desafíos utilizan cifrados clásicos como **César, Atbash y Vigenère**, con frases y parámetros variables entre partidas.

## Objetivo educativo

El juego busca que los alumnos comprendan cómo funcionan los cifrados clásicos, identifiquen patrones y practiquen el razonamiento lógico. El formato por equipos permite trabajar también la comunicación y la resolución colaborativa de problemas.

El profesor dispone de una vista de seguimiento para acompañar a los grupos y detectar dónde necesitan ayuda.

## Estado del proyecto

Actualmente están implementados el registro y acceso de profesores, la creación de sesiones, la configuración de dificultad y duración, los equipos, el acceso de alumnos y los lobbies con actualización periódica.

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

Para ejecutar las pruebas y generar los recursos de producción:

```sh
php artisan test
npm run build
```
