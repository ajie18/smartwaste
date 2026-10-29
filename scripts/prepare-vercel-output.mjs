import { cpSync, mkdirSync, rmSync } from 'node:fs';
import { basename, join, relative } from 'node:path';

const publicDirectory = join(process.cwd(), 'public');
const outputDirectory = join(process.cwd(), 'dist');

rmSync(outputDirectory, { force: true, recursive: true });
mkdirSync(outputDirectory, { recursive: true });

cpSync(publicDirectory, outputDirectory, {
    recursive: true,
    filter(source) {
        const path = relative(publicDirectory, source);
        const filename = basename(source);

        return path === '' || (filename !== '.htaccess' && !filename.endsWith('.php'));
    },
});
