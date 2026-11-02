# CryptoDungeon

CryptoDungeon es una aplicación web educativa que convierte el aprendizaje de la criptografía en un escape room. Los alumnos exploran una mazmorra 2D y resuelven mensajes cifrados para desbloquear habitaciones y avanzar hasta la salida.

Está pensada para utilizarse en el aula: una persona crea la sala y organiza la partida, y los demás participan individualmente o por equipos. Todos acceden desde el navegador, sin registro ni inicio de sesión.

## Cómo se juega

1. El anfitrión crea una sala y elige la dificultad: fácil (5 acertijos), normal (10) o difícil (15). En modo por equipos, crea los grupos.
2. Los alumnos se unen con un código de acceso de seis cifras y un nombre o alias.
3. El anfitrión inicia la partida cuando hay al menos dos jugadores conectados en individual o al menos dos equipos, cada uno con un jugador conectado.
4. Cada equipo resuelve los acertijos de las habitaciones y avanza por su propio recorrido.
5. Cada respuesta correcta abre la siguiente habitación y suma 100 puntos al equipo. La partida termina cuando todos han completado el recorrido.

El plano representa salas de piedra conectadas por pasillos rectos. Cada jugador tiene su propia ficha y puede volver a una sala contigua abierta pulsándola. Al resolver un acertijo, quien responde avanza a la siguiente habitación; sus compañeros conservan su ubicación y comparten los desbloqueos del equipo.

El anfitrión ve las posiciones de todos los jugadores en su pantalla, actualizadas cada tres segundos. Cada participante ve las fichas de su propio equipo. Los colores identifican los grupos y las fichas atenuadas indican jugadores sin señal reciente.

El botón «Activar ambiente» reproduce una composición original incluida en `public/audio`, con pausa y control de volumen. La música es opcional y se inicia al pulsar el botón. Para cambiarla, sustituye el archivo por una pista propia o cuya licencia permita distribuirla en la aplicación; consulta [los detalles del audio](public/audio/README.md).

La partida no tiene límite de tiempo. Los recorridos contienen 5, 10 o 15 habitaciones conectadas, según la dificultad. Todos incluyen **Cifrado César, Cifrado de Sustitución Simple y Cifrado de Vigenère**, con frases y claves variables entre partidas.

## Generación de acertijos

Al iniciar una partida, el servidor combina plantillas de frases de la mazmorra con listas de palabras compatibles. Por ejemplo, «LA LLAVE DE ORO ABRE LA PUERTA DEL ARCHIVO» puede variar el material y el lugar sin perder coherencia. Las plantillas están en `resources/data/phrases.php`.

Se seleccionan frases distintas y se reparten los tres cifrados de forma equilibrada. César usa un desplazamiento aleatorio; Sustitución Simple, un alfabeto permutado sin letras que permanezcan en su posición; Vigenère, una clave del banco de palabras. Las ayudas de cada algoritmo se muestran junto al mensaje.

La generación se realiza una sola vez: todos los equipos reciben los mismos desafíos y cada equipo tiene su propio progreso. Las soluciones se guardan cifradas y la validación se hace en el backend; el navegador recibe el mensaje cifrado y sus parámetros de resolución. La respuesta admite diferencias de tildes, mayúsculas, espacios y puntuación.

## Objetivo educativo

El juego busca que los alumnos comprendan cómo funcionan los cifrados clásicos, identifiquen patrones y practiquen el razonamiento lógico. El formato por equipos permite trabajar también la comunicación y la resolución colaborativa de problemas.

El anfitrión dispone de una vista de seguimiento para acompañar a los grupos y detectar dónde necesitan ayuda.

El control de la sala se conserva en la sesión del navegador de quien la crea. El código permite unirse como participante, pero no gestionar los grupos. Para volver a administrar una sala, hay que utilizar el mismo navegador y conservar sus cookies.

## Estado del proyecto

Están implementados las salas sin cuenta, las dificultades por cantidad de acertijos, la gestión de grupos, el acceso con código, el inicio de partida, el mapa 2D, los tres cifrados, la validación de respuestas, el progreso independiente y el seguimiento de puntuaciones mediante polling. No hay temporizador ni sistema de pistas con penalizaciones.

## Tecnologías

| Área | Tecnología |
| --- | --- |
| Backend | PHP y Laravel |
| Interfaz | Blade, Livewire y Alpine.js |
| Estilos y mapa 2D | Tailwind CSS y CSS Grid |
| Base de datos | SQLite en local; PostgreSQL de Neon en Vercel |
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
