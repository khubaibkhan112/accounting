# Build Instructions

## Frontend Assets Build

### Development Build

For development with hot module replacement:

```bash
npm run dev
```

This will:
- Start Vite development server
- Enable hot module replacement (HMR)
- Watch for file changes
- Serve assets on development server

### Production Build

For production deployment:

```bash
npm run build
```

This will:
- Compile Vue.js components
- Minify JavaScript
- Process Tailwind CSS
- Optimize assets for production
- Generate production-ready files in `public/build/`

### Build Output

After building, the following files are generated:
- `public/build/assets/` - Compiled JavaScript and CSS files
- `public/build/manifest.json` - Asset manifest for Laravel Vite plugin

### Environment-Specific Builds

The build process uses environment variables from `.env`:
- `APP_ENV` - Environment (local, staging, production)
- `APP_DEBUG` - Debug mode (affects source maps)

### Troubleshooting

#### Build Fails
1. Clear node_modules and reinstall:
```bash
rm -rf node_modules package-lock.json
npm install
```

2. Clear Vite cache:
```bash
rm -rf node_modules/.vite
```

3. Rebuild:
```bash
npm run build
```

#### Assets Not Loading
1. Clear Laravel cache:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

2. Rebuild assets:
```bash
npm run build
```

#### Production Optimization

After building for production:

1. Optimize Laravel:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. Optimize Composer:
```bash
composer install --optimize-autoloader --no-dev
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Build Assets

on:
  push:
    branches: [ main ]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      - run: npm ci
      - run: npm run build
      - uses: actions/upload-artifact@v3
        with:
          name: build-assets
          path: public/build
```

## Build Scripts

### package.json Scripts

- `npm run dev` - Development server with HMR
- `npm run build` - Production build
- `npm run build:watch` - Build and watch for changes (if configured)

### Custom Build Script

Create `scripts/build.sh`:

```bash
#!/bin/bash
set -e

echo "Building frontend assets..."

# Install dependencies
npm ci

# Build for production
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Build completed successfully!"
```

Make executable:
```bash
chmod +x scripts/build.sh
```

Run:
```bash
./scripts/build.sh
```
