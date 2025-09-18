#!/usr/bin/env node

/**
 * Script to generate a clean zip file of the Shahbandr Stream plugin
 * Excludes development files and directories
 */

const fs = require('fs-extra');
const path = require('path');
const archiver = require('archiver');

// Configuration
const PLUGIN_NAME = 'product-availability-checker';
const SOURCE_DIR = __dirname;
const BUILD_DIR = path.join(__dirname, 'build');
const ZIP_FILE = path.join(BUILD_DIR, `${PLUGIN_NAME}.zip`);

// List of files and directories to exclude
const EXCLUDED_PATTERNS = [
    // Development directories
    'node_modules',
    '.git',
    '.github',
    '.vscode',
    'vendor',

    // Development files
    '.gitignore',
    'composer.json',
    'composer.lock',
    'phpcs.xml.dist',
    '.phpcs.xml.dist',
    'package.json',
    'package-lock.json',
    'README.md',

    // The build script itself
    'build-zip.js',

    // Temporary files
    '.DS_Store',
    'Thumbs.db',
    '*.log',
    '*.tmp',

    // Dist directory (will be created again)
    'build'
];

// Ensure build directory exists
fs.ensureDirSync(BUILD_DIR);

// Create a file to stream archive data to
const output = fs.createWriteStream(ZIP_FILE);
const archive = archiver('zip', {
    zlib: { level: 9 } // Maximum compression
});

// Listen for all archive data to be written
output.on('close', function () {
    console.log(`✅ Archive created successfully: ${ZIP_FILE}`);
    console.log(`📦 Total size: ${(archive.pointer() / 1024 / 1024).toFixed(2)} MB`);
});

// Listen for warnings
archive.on('warning', function (err) {
    if (err.code === 'ENOENT') {
        console.warn('⚠️ Warning:', err);
    } else {
        throw err;
    }
});

// Listen for errors
archive.on('error', function (err) {
    throw err;
});

// Pipe archive data to the output file
archive.pipe(output);

/**
 * Check if a file or directory should be excluded
 * @param {string} filePath - Path to check
 * @returns {boolean} - True if it should be excluded
 */
function shouldExclude(filePath) {
    // Normalize the path to use forward slashes consistently
    const relativePath = path.relative(SOURCE_DIR, filePath).replace(/\\/g, '/');

    // Skip the file if it matches any excluded pattern
    return EXCLUDED_PATTERNS.some(pattern => {
        // Normalize pattern to use forward slashes
        const normalizedPattern = pattern.replace(/\\/g, '/');

        if (normalizedPattern.startsWith('*')) {
            // Handle wildcard extensions
            const ext = normalizedPattern.replace('*', '');
            return relativePath.endsWith(ext);
        }

        // Check for exact match or directory prefix
        return relativePath === normalizedPattern ||
            relativePath.startsWith(normalizedPattern + '/') ||
            // Also check with trailing slash for directories
            (normalizedPattern.endsWith('/') && relativePath.startsWith(normalizedPattern));
    });
}

/**
 * Add directory contents to the archive
 * @param {string} directory - Directory to archive
 */
function addDirectoryToArchive(directory) {
    const files = fs.readdirSync(directory);

    for (const file of files) {
        const filePath = path.join(directory, file);

        // Skip excluded files and directories
        if (shouldExclude(filePath)) {
            console.log(`⏩ Skipping: ${path.relative(SOURCE_DIR, filePath)}`);
            continue;
        }

        const stats = fs.statSync(filePath);
        const relativePath = path.relative(SOURCE_DIR, filePath);

        if (stats.isDirectory()) {
            // Recursively add directory contents
            addDirectoryToArchive(filePath);
        } else {
            // Add file to archive
            archive.file(filePath, { name: path.join(PLUGIN_NAME, relativePath) });
            console.log(`📄 Adding: ${relativePath}`);
        }
    }
}

console.log('🚀 Building plugin zip file...');
console.log(`📁 Source directory: ${SOURCE_DIR}`);
console.log(`📁 Output directory: ${BUILD_DIR}`);

// Check if node_modules exists, if not run npm install
if (!fs.existsSync(path.join(SOURCE_DIR, 'node_modules'))) {
    console.log('📦 node_modules not found. Running npm install...');
    const { execSync } = require('child_process');
    try {
        execSync('npm install', { stdio: 'inherit', cwd: SOURCE_DIR });
        console.log('✅ npm install completed successfully');
    } catch (error) {
        console.error('❌ Error running npm install:', error.message);
        process.exit(1);
    }
}

// Check if build folder exists, if not run npm run build
if (!fs.existsSync(path.join(SOURCE_DIR, 'build'))) {
    console.log('🔨 Build folder not found. Running npm run build...');
    const { execSync } = require('child_process');
    try {
        execSync('npm run build', { stdio: 'inherit', cwd: SOURCE_DIR });
        console.log('✅ Build completed successfully');
    } catch (error) {
        console.error('❌ Error running build:', error.message);
        process.exit(1);
    }
}

// Before we start, make sure build files exist
if (!fs.existsSync(path.join(SOURCE_DIR, 'build'))) {
    console.warn('⚠️ Warning: build directory not found. Make sure you have run "npm run build" first.');
}

// Add plugin files to the archive
addDirectoryToArchive(SOURCE_DIR);

// Finalize the archive
archive.finalize();