// Post-build PWA helper.
// vite-plugin-pwa emits sw.js + manifest.webmanifest inside public/build/
// because Laravel-Vite uses /build/ as its base URL. To register the service
// worker with scope "/" (so it can intercept the whole site), the file must be
// served from /sw.js — Laravel's static handler serves public/sw.js directly.
//
// This script:
//   1. Copies public/build/sw.js → public/sw.js, rewriting precache URLs from
//      relative ("assets/foo.js") to absolute ("/build/assets/foo.js") so the
//      service worker can still find them after being moved up one level.
//   2. Copies public/build/manifest.webmanifest → public/manifest.webmanifest.
//
// The generated files in public/ are gitignored.

import fs from 'node:fs';
import path from 'node:path';

const buildDir = 'public/build';
const publicDir = 'public';

const swSource = path.join(buildDir, 'sw.js');
const manifestSource = path.join(buildDir, 'manifest.webmanifest');

if (!fs.existsSync(swSource)) {
    console.warn('[pwa] sw.js not found in public/build/, skipping.');
    process.exit(0);
}

let swContent = fs.readFileSync(swSource, 'utf8');
const rewrite = (prefix) =>
    new RegExp(`${prefix}url:"((?!https?://|/)[^"]+)"`, 'g');
swContent = swContent
    .replace(rewrite('"'), '"url":"/build/$1"')
    .replace(rewrite('(?<![\\w$])'), 'url:"/build/$1"');

fs.writeFileSync(path.join(publicDir, 'sw.js'), swContent);
console.log('[pwa] public/sw.js written with absolute precache URLs.');

if (fs.existsSync(manifestSource)) {
    fs.copyFileSync(manifestSource, path.join(publicDir, 'manifest.webmanifest'));
    console.log('[pwa] public/manifest.webmanifest copied.');
}
