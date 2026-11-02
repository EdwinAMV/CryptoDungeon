# Ambiente de CryptoDungeon

El reproductor detecta los archivos MP3, M4A, OGG, WAV, WebM y AAC de esta carpeta. Actualmente utiliza `lygus-theme.mp3`, `return-to-silence.mp3` y `samvartasthayi.mp3`.

Se elige una pista al azar al pulsar «Activar ambiente». Cuando termina, se reproduce otra aleatoria sin repetir inmediatamente la anterior si hay varias. Pausar y reanudar conserva la pista y su posición; el polling tampoco reinicia el audio. Se mantiene el control de volumen.

Para añadir o quitar pistas, cambia los archivos de esta carpeta y vuelve a desplegar. Los nombres con espacios se codifican en las URL. El README y los archivos que no sean de audio quedan fuera de la lista. Utiliza pistas propias o con permiso para publicarlas en la aplicación.

El build de Vercel copia la carpeta `public/audio` a la salida estática. No descargues canciones de YouTube para redistribuirlas sin disponer de los derechos necesarios. Los enlaces de YouTube no son archivos de audio para un elemento `<audio>`; requieren su reproductor de vídeo.
