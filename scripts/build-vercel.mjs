import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import { resolve } from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const projectRoot = fileURLToPath(new URL('../', import.meta.url));
const build = spawnSync(process.execPath, [resolve(projectRoot, 'node_modules/vite/bin/vite.js'), 'build'], {
    cwd: projectRoot,
    stdio: 'inherit',
});

if (build.error || build.status !== 0) {
    throw build.error ?? new Error(`Vite terminó con código ${build.status}.`);
}

// Only explicitly public assets go to the CDN. Never copy public/index.php,
// public/hot, local storage, the application source or environment files.
const output = resolve(projectRoot, 'dist');
rmSync(output, { recursive: true, force: true });
mkdirSync(output, { recursive: true });

for (const asset of ['build', 'audio', 'favicon.ico', 'robots.txt']) {
    const source = resolve(projectRoot, 'public', asset);
    if (existsSync(source)) {
        cpSync(source, resolve(output, asset), { recursive: true });
    }
}

if (!existsSync(resolve(output, 'build/manifest.json'))) {
    throw new Error('Falta el manifiesto de Vite en la salida estática.');
}

console.log('Recursos públicos de Vercel preparados en dist/.');
