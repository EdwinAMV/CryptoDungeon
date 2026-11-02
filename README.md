# CryptoDungeon

CryptoDungeon es una aplicación web educativa que convierte el aprendizaje de la criptografía en un escape room. Los alumnos exploran una mazmorra 2D y resuelven mensajes cifrados para desbloquear habitaciones y avanzar hasta la salida.

Está pensada para utilizarse en el aula: una persona crea la sala y organiza la partida, y los demás participan individualmente o por equipos. Todos acceden desde el navegador, sin registro ni inicio de sesión.

## Cómo se juega

1. El anfitrión crea una sala y elige la dificultad: fácil (5 acertijos), normal (10) o difícil (15). Se genera la mazmorra y se descarga un TXT con las respuestas, accesible solo al creador. En modo por equipos, crea los grupos.
2. Los alumnos se unen con un código de acceso de seis cifras y un nombre o alias.
3. El anfitrión inicia la partida cuando hay al menos dos jugadores conectados en individual o al menos dos equipos, cada uno con un jugador conectado.
4. Cada equipo resuelve los acertijos de las habitaciones y avanza por su propio recorrido.
5. Cada respuesta correcta abre la siguiente habitación y suma hasta 100 puntos al equipo: cada fallo previo en esa habitación resta 10, sin límite inferior. La partida termina cuando todos han completado el recorrido.

El plano representa salas de piedra conectadas por pasillos rectos. La última habitación siempre se llama «Salida», también en partidas existentes, y conserva su acertijo final. Cada jugador tiene su propia ficha y puede volver a una sala contigua abierta pulsándola. Al resolver un acertijo, quien responde avanza a la siguiente habitación; sus compañeros conservan su ubicación y comparten los desbloqueos del equipo.

El anfitrión ve las posiciones de todos los jugadores en su pantalla, actualizadas cada tres segundos. Cada participante ve las fichas de su propio equipo. Cada jugador conserva un color propio, asignado al azar y distinto de los de sus compañeros; el borde de la ficha identifica el grupo. Las fichas atenuadas indican jugadores sin señal reciente.

Solo el anfitrión dispone del botón «Activar ambiente», que reproduce al azar las pistas incluidas en `public/audio`, con pausa y control de volumen. Al terminar una pista, elige otra sin repetir inmediatamente la anterior. La música intenta iniciarse automáticamente en la pantalla del anfitrión y mantiene los controles de pausa y volumen. Si el navegador exige interacción para reproducir sonido, vuelve a intentarlo con el primer clic o pulsación de tecla en la página, o mediante «Activar ambiente». Para cambiarla, añade o elimina archivos de audio y vuelve a desplegar; consulta [los detalles del audio](public/audio/README.md).

La partida no tiene límite de tiempo. Los recorridos contienen 5, 10 o 15 habitaciones conectadas, según la dificultad. Todos incluyen **Cifrado César, Cifrado de Sustitución Simple y Cifrado de Vigenère**, con frases y claves variables entre partidas.

El creador puede eliminar la sala y todos sus datos desde su panel, previa confirmación. Los jugadores pueden salir desde el lobby o durante la partida, y disponen de un botón de salida al completar su recorrido. Salir durante la partida conserva los puntos del equipo y el historial de respuestas, pero impide volver a entrar. Los equipos sin participantes activos no bloquean el final de la partida.

El enlace «Descargar respuestas (.txt)» del panel permite descargar de nuevo las respuestas de esa misma mazmorra. Cada descarga comprueba los permisos del creador y no guarda archivos en carpetas públicas. Si el navegador bloquea la descarga automática inicial, se puede utilizar ese enlace.

## Puntuación, intentos y chat

Cada habitación parte de 100 puntos. Al resolverla se conceden `100 - 10 × fallos previos`, que pueden ser negativos; por ejemplo, 12 fallos dejan la habitación en −20 puntos. En equipos se suman los fallos de todos sus miembros en esa habitación. No se restan puntos de otras habitaciones, y los puntos ya concedidos en partidas anteriores se conservan. Las respuestas repetidas sobre una habitación resuelta no cuentan como nuevos intentos.

El resumen final muestra intentos correctos e incorrectos, el detalle por jugador y habitación y la puntuación obtenida. El anfitrión también ve los intentos y fallos de cada grupo y participante en su panel.

En modo por equipos hay un chat privado en el lobby y durante la partida, disponible hasta salir de la sala. Se abre desde el botón flotante de la esquina inferior derecha, se puede ocultar sin perder el borrador y se cierra también con Escape. Cada autor utiliza su mismo color personal en el chat y el mapa. Muestra los últimos 50 mensajes, actualizados cada tres segundos. Cada mensaje admite 500 caracteres y cada participante puede enviar hasta 10 mensajes en 30 segundos. El servidor obtiene el grupo de la sesión del navegador: no se aceptan identificadores de equipo enviados por el cliente. Los mensajes se muestran como texto y se eliminan al borrar la sala. El chat no aparece en partidas individuales ni en el panel del anfitrión.

## Generación de acertijos

Al crear una sala, el servidor combina plantillas de frases de la mazmorra con listas de palabras compatibles. Por ejemplo, «LA LLAVE DE ORO ABRE LA PUERTA DEL ARCHIVO» puede variar el material y el lugar sin perder coherencia. Las plantillas están en `resources/data/phrases.php`.

Se seleccionan frases distintas y se reparten los tres cifrados de forma equilibrada. César usa un desplazamiento aleatorio; Sustitución Simple, un alfabeto permutado sin letras que permanezcan en su posición; Vigenère, una clave del banco de palabras. Las ayudas de cada algoritmo se muestran junto al mensaje.

La generación se realiza una sola vez y se conserva al iniciar la partida: todos los equipos reciben los mismos desafíos y cada equipo tiene su propio progreso. Las soluciones se guardan cifradas y la validación se hace en el backend; el navegador del jugador recibe el mensaje cifrado y sus parámetros de resolución. Solo el anfitrión puede descargar las soluciones mediante el endpoint protegido del TXT. La respuesta admite diferencias de tildes, mayúsculas, espacios y puntuación. El bloque del mensaje cifrado impide seleccionar, copiar y arrastrar el texto desde la interfaz, sin afectar al campo de respuesta ni al chat. Esta medida disuade la copia directa; no impide capturas, OCR o la inspección del navegador.

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

La aplicación concentra las reglas del juego en el servidor: la validación de respuestas, el progreso y las puntuaciones se calculan en el backend. Las soluciones de los acertijos permanecen fuera de los datos enviados a los jugadores; únicamente el creador tiene acceso al TXT de respuestas.

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
