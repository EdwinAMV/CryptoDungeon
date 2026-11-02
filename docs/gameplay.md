# Partidas y acertijos

El anfitrión pulsa «Empezar partida» en el lobby. El botón se habilita cuando se cumplen los requisitos y el servidor vuelve a comprobarlos dentro de una transacción:

- Individual: al menos dos jugadores con actividad reciente.
- Equipos: al menos dos equipos y un jugador con actividad reciente en cada uno. Un equipo vacío o desconectado impide iniciar.
- La actividad reciente se determina mediante el heartbeat del lobby; el umbral actual es de 30 segundos, configurable en `config/cryptodungeon.php`.

No hay temporizador. La dificultad determina exclusivamente la cantidad de acertijos: fácil 5, normal 10, difícil 15. Las columnas históricas de duración se conservan para actualizar la base sin borrar datos ni romper el despliegue anterior; las nuevas partidas no las utilizan.

Al iniciar se genera una cuadrícula con un recorrido serpenteante, variando su anchura y orientación. Dos habitaciones consecutivas siempre comparten un lado. No existe movimiento libre: resolver cada mensaje desbloquea la siguiente habitación del equipo.

`PhraseGenerator` expande un banco de seis plantillas con listas compatibles (59 frases posibles actualmente), mezcla el resultado y elige frases sin repetir. El texto es temático y coherente porque las combinaciones están revisadas, sin llamadas a modelos externos. Para ampliar el contenido, edita `resources/data/phrases.php` manteniendo la concordancia de cada lista.

`PuzzleGenerator` distribuye César, Sustitución Simple y Vigenère de forma equilibrada. Cada partida tiene claves nuevas y todos los equipos comparten sus desafíos. Las soluciones están cifradas en la tabla `rooms`; no se incluyen en las propiedades públicas de Livewire ni en los datos de los mapas. Conserva la misma `APP_KEY` de producción entre despliegues.

Cada equipo tiene filas propias de `team_room_progress`. `GameService` valida las respuestas, impide saltar habitaciones, registra el resultado de cada intento y suma 100 puntos una sola vez por habitación. Las transacciones y las restricciones únicas evitan duplicar avances y puntuaciones ante reenvíos de compañeros. No se almacenan los textos enviados en los intentos.

Al comenzar, el polling lleva a los alumnos del lobby al primer acertijo. Los compañeros comparten el progreso. Un equipo que acaba espera al resto y la partida finaliza cuando todos terminan. La vista del anfitrión muestra el avance y los puntos sin mostrar soluciones.

La migración `2026_11_02_000002_add_playable_dungeons` añade las tablas y permite salas sin duración. En local aplica `php artisan migrate`; el build de Vercel ya ejecuta las migraciones contra Neon. No es necesario cambiar variables de entorno ni reiniciar bases existentes.
