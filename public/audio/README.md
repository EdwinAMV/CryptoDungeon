# Ambiente de CryptoDungeon

`dungeon-ambient.m4a` es una composición procedural de 48 segundos creada para este proyecto: acordes suaves y notas de cristal, sin grabaciones, melodías ni muestras descargadas de terceros. Se genera con `scripts/generate-ambient.py` y se comprime en AAC.

El reproductor se inicia solo al pulsar «Activar ambiente», permite pausar y ajustar el volumen, y no se reinicia con el polling. Puedes sustituir el archivo por otra pista propia o con una licencia que permita publicarla en la aplicación.

El build de Vercel copia la carpeta `public/audio` a la salida estática. No descargues canciones de YouTube para redistribuirlas sin disponer de los derechos necesarios. Los enlaces de YouTube no son archivos de audio para un elemento `<audio>`; requieren su reproductor de vídeo.
