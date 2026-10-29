import { cpSync, copyFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';

const publicDirectory = join(process.cwd(), 'public');
const outputDirectory = join(process.cwd(), 'dist');

mkdirSync(outputDirectory, { recursive: true });
cpSync(join(publicDirectory, 'build'), join(outputDirectory, 'build'), { recursive: true });

for (const filename of ['favicon.ico', 'robots.txt']) {
    copyFileSync(join(publicDirectory, filename), join(outputDirectory, filename));
}
