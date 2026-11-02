# CryptoDungeon

CryptoDungeon es una aplicación web educativa que convierte el aprendizaje de la criptografía en un escape room. Los jugadores exploran una mazmorra 2D, descifran mensajes y desbloquean habitaciones hasta alcanzar la **Salida**.

Está pensada para el aula: una persona crea y organiza la sala, y los demás participan individualmente o por equipos desde el navegador. No requiere cuentas ni inicio de sesión.

## Cómo se juega

1. El anfitrión crea una sala, selecciona el modo y elige la cantidad de acertijos mediante la dificultad.
2. Se genera la mazmorra y se solicita la descarga de un TXT con sus respuestas, disponible únicamente para el creador. En modo por equipos, el anfitrión crea los grupos.
3. Los jugadores entran con el código de seis cifras y un nombre o alias. Si juegan por equipos, seleccionan su grupo.
4. El anfitrión inicia la partida cuando hay al menos **dos jugadores conectados** en individual, o **dos equipos como mínimo, cada uno con al menos un jugador conectado**.
5. Cada jugador o equipo resuelve los mensajes para abrir la siguiente habitación, acumular puntos y completar el recorrido.

| Dificultad | Acertijos |
| --- | ---: |
| Fácil | 5 |
| Normal | 10 |
| Difícil | 15 |

La dificultad cambia la cantidad de acertijos, no los algoritmos utilizados. **No hay temporizador.** Todos los recorridos incluyen César, Sustitución Simple y Vigenère. La sala admite hasta 100 participantes y, en modo por equipos, hasta 30 grupos.

El progreso es independiente entre equipos. La partida termina cuando los equipos que todavía tienen participantes han completado su recorrido; los equipos que se quedan vacíos no bloquean el final.

## Mapa y seguimiento

El mapa utiliza salas rectangulares de piedra y pasillos rectos, con un recorrido conectado, puertas, decoración y controles de zoom. La última habitación siempre se llama **Salida** y conserva su acertijo final.

Cada jugador tiene su propia ficha y puede pulsar una sala contigua desbloqueada para desplazarse. Al resolver un mensaje, quien responde avanza automáticamente a la siguiente habitación. Sus compañeros conservan su ubicación y comparten los desbloqueos del equipo.

Cada participante recibe un color estable, distinto de los de sus compañeros, que utiliza en el mapa y en el chat. El borde de la ficha identifica al equipo; las iniciales y los nombres ayudan a identificar a cada jugador.

El anfitrión ve las posiciones de todos los jugadores y el progreso, la puntuación y los intentos de cada equipo y participante. Los jugadores ven las fichas de su propio equipo y la clasificación general. El seguimiento utiliza **polling cada tres segundos con la pestaña activa**, sin WebSockets. Una ficha atenuada o el estado «Sin señal» indican falta de actividad reciente, no una salida explícita de la sala.

## Cifrados y generación de acertijos

Los desafíos se generan en el servidor al crear la sala y se conservan al empezar la partida. Todos los equipos reciben los mismos mensajes, pero avanzan de forma independiente.

Las frases se construyen combinando plantillas y vocabulario compatible de [resources/data/phrases.php](resources/data/phrases.php), sin servicios externos de IA. Se eligen frases distintas por partida y se reparten los tres cifrados de forma equilibrada:

| Cifrado | Generación y ayuda de resolución |
| --- | --- |
| César | Desplazamiento aleatorio, indicado junto al mensaje. |
| Sustitución Simple | Alfabeto permutado sin letras que permanezcan en su posición; se muestran los alfabetos original y cifrado. |
| Vigenère | Clave seleccionada del banco de palabras, mostrada junto a las instrucciones. |

Se utiliza el alfabeto A–Z, sin Ñ ni tildes. La validación de respuestas ignora diferencias de mayúsculas, tildes, espacios y puntuación.

Las soluciones se guardan cifradas y se validan en el backend. No se incluyen en los datos enviados a los jugadores. El creador puede descargar el TXT mediante una ruta que comprueba sus permisos en cada petición; no se guarda un archivo público con las respuestas. Si el navegador bloquea la descarga automática inicial, el panel ofrece el enlace **«Descargar respuestas (.txt)»**.

El bloque del mensaje cifrado impide seleccionar, copiar, arrastrar y abrir el menú contextual desde la interfaz. El campo de respuesta y el chat mantienen su edición normal. Esta medida dificulta la copia directa, pero no impide capturas, OCR ni la inspección del navegador.

## Puntuación y resultados

Cada habitación parte de 100 puntos. Al resolverla se concede:

```text
Puntos de la habitación = 100 − 10 × respuestas incorrectas previas
```

La puntuación puede ser negativa: **12 fallos dejan el premio de esa habitación en −20 puntos**. En equipos se acumulan los fallos de todos sus miembros en esa habitación. No se descuentan puntos de otras habitaciones ni se recalculan premios ya concedidos.

Los intentos cuentan las respuestas correctas e incorrectas registradas por el servidor. Reenviar una respuesta a una habitación ya resuelta no añade intentos ni duplica puntos.

Al completar el recorrido aparece un resumen con los intentos y fallos por jugador y habitación, y los puntos obtenidos. El anfitrión también puede consultar estos recuentos en su panel.

## Chat por equipos

El chat privado está disponible en el lobby, durante la partida y después de completar el recorrido, hasta salir de la sala. Se abre con el botón flotante de la esquina inferior derecha y se puede ocultar, también con Escape, sin perder el borrador.

Cada autor aparece con su nombre y color personal. El chat muestra los últimos 50 mensajes, se actualiza cada tres segundos y permite mensajes de hasta 500 caracteres. Cada participante puede enviar hasta 10 mensajes en 30 segundos.

El servidor identifica al jugador y su grupo mediante la sesión del navegador; no acepta identificadores de equipo elegidos por el cliente. Los mensajes se muestran como texto. El chat no aparece en partidas individuales ni en el panel del anfitrión, y sus mensajes se eliminan al borrar la sala.

## Música ambiental

La música se reproduce **solo en la pantalla del anfitrión**, con controles de pausa y volumen. Intenta arrancar automáticamente; si el navegador exige interacción para permitir sonido, reintenta la reproducción con el primer clic o pulsación de tecla en la página. El botón «Activar ambiente» permite iniciarla también manualmente.

La aplicación elige una pista aleatoria y, al terminar, selecciona otra sin repetir inmediatamente la anterior cuando hay varias. Pausar y reanudar conserva la pista y su posición, y el polling no reinicia la reproducción.

Para cambiar la selección, añade o elimina archivos directamente en **[public/audio/](public/audio/)**:

- Se detectan automáticamente archivos MP3, M4A, OGG, WAV, WebM y AAC; la reproducción depende de los formatos admitidos por el navegador.
- No hace falta modificar código ni variables de entorno. No se exploran subcarpetas.
- Para actualizar la música en producción, incluye los archivos en el repositorio y vuelve a desplegar en Vercel.
- Utiliza audios propios o con permiso para distribuirlos en la aplicación.

Consulta [los detalles del audio](public/audio/README.md).

## Gestión de salas y salidas

El control de las salas está asociado a la sesión del navegador del creador. El código público permite unirse como participante, pero no administrar grupos, descargar respuestas ni borrar salas. Para seguir gestionándolas hay que conservar esa sesión y sus cookies; no existe recuperación mediante una cuenta.

Los jugadores pueden salir desde el lobby o durante la partida. Salir del lobby libera su plaza; salir una vez iniciada la partida conserva los puntos y el historial del equipo, pero impide volver a entrar. Al completar su recorrido disponen de un botón de salida sin tener que esperar a los demás equipos.

El anfitrión puede eliminar una sala desde su panel, previa confirmación. Se borran sus equipos, participantes, acertijos, progreso, intentos y mensajes de chat. Los jugadores conectados reciben un aviso cuando su pantalla se actualiza.

## Tecnologías

| Área | Tecnología |
| --- | --- |
| Backend | PHP y Laravel 13 |
| Interfaz | Blade, Livewire 4 y Alpine.js incluido por Livewire |
| Estilos y mapa | Tailwind CSS 4 y CSS Grid |
| Recursos frontend | Vite |
| Base de datos | SQLite en local; PostgreSQL de Neon en Vercel |
| Actualizaciones | Polling con Livewire |
| Entorno local | Laravel Herd, sin Docker |

Las reglas de acceso, validación, progreso y puntuación se aplican en el servidor. No hay temporizador ni sistema de pistas con penalizaciones; las ayudas de cada cifrado se muestran junto al mensaje.

## Instalación y desarrollo local

### Requisitos

- Laravel Herd con PHP 8.5 y soporte para SQLite. Las dependencias del `composer.lock` actual requieren al menos PHP 8.4.1, aunque `composer.json` declare `^8.3`.
- Composer.
- Node.js 26.13.0, indicado en [.nvmrc](.nvmrc), y npm compatible con `engines` en [package.json](package.json).

Las dependencias se fijan mediante `composer.lock` y `package-lock.json`. Si utilizas nvm, puedes seleccionar la versión del proyecto con `nvm install` y `nvm use`.

### Primera instalación

Desde la raíz de una copia nueva del proyecto:

```sh
cp .env.example .env
composer install
composer check-platform-reqs
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm ci --ignore-scripts
npm run build
```

En `.env`, mantén `DB_CONNECTION=sqlite` y configura `APP_URL` con la dirección local asignada por Herd. El proyecto no necesita Redis, un servidor de colas ni cuentas de profesor para jugar. No es necesario ejecutar seeders.

Añade la carpeta a Herd y abre la URL que le asigne, por ejemplo `http://cryptodungeon.test`. Utiliza `https://cryptodungeon.test` si has activado HTTPS en Herd.

Para trabajar con recarga automática del frontend, mantén este comando en otra terminal mientras Herd sirve Laravel:

```sh
npm run dev
```

### Actualizar una instalación existente

Conserva tu `.env`, la base de datos y `APP_KEY`. Después de incorporar cambios o dependencias:

```sh
composer install
npm ci --ignore-scripts
php artisan migrate
npm run build
```

Las migraciones actualizan el esquema conservando los registros existentes. No uses `migrate:fresh` para una instalación con partidas que quieras conservar. Tampoco regeneres `APP_KEY`: se utiliza para cifrar las soluciones y proteger las cookies de sesión.

### Verificación

```sh
php artisan test
npm run build
```

Las pruebas utilizan SQLite en memoria y cubren el acceso a salas, los permisos del anfitrión, la generación de acertijos, los movimientos, las puntuaciones, las salidas y la privacidad del chat, entre otros comportamientos. Algunas pruebas del reproductor ejecutan Node.js, por lo que debe estar disponible también al ejecutar la suite PHP.

Para comprobar además la preparación de recursos estáticos de Vercel:

```sh
npm run build:vercel
```

## Dónde personalizar el proyecto

| Archivo o carpeta | Contenido |
| --- | --- |
| [resources/data/phrases.php](resources/data/phrases.php) | Plantillas y vocabulario de los mensajes. |
| [config/cryptodungeon.php](config/cryptodungeon.php) | Cantidad de acertijos por dificultad y umbral de presencia. |
| [app/Services/ScoreCalculator.php](app/Services/ScoreCalculator.php) | Fórmula de puntuación. |
| [resources/css/app.css](resources/css/app.css) | Estilos de la interfaz, mapa y chat flotante. |
| [public/audio/](public/audio/) | Pistas de música detectadas automáticamente. |

## Publicación en Vercel

El proyecto incluye una adaptación para ejecutar Laravel con el runtime comunitario `vercel-php` y una base PostgreSQL externa en Neon. Vite compila los recursos; la aplicación sigue necesitando el backend PHP.

La configuración de [vercel.json](vercel.json) utiliza:

| Ajuste | Valor |
| --- | --- |
| Framework Preset | Other |
| Install Command | `npm ci --ignore-scripts` |
| Build Command | `npm run build:vercel` |
| Output Directory | `dist` |

Las variables se configuran en Vercel a partir de [.env.vercel.example](.env.vercel.example), sin subir secretos. El hook de despliegue aplica las migraciones a Neon; las salas de SQLite local no se transfieren automáticamente a producción.

Los pasos de conexión, variables, detección automática de `APP_URL` y diagnóstico están en la [guía de Vercel y Neon](docs/vercel.md).
