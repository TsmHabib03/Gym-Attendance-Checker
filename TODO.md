# Fix Tailwind CDN & ZXing Cache Issues

## Steps
- [x] 1. Analyze files and identify Tailwind usage, ZXing cache root cause, and gitignore stance
- [x] 2. Remove Tailwind CDN from `views/partials/head.php` and add custom CSS utility replacements
- [x] 3. Update `views/members/qr.php` to use semantic grid class instead of arbitrary Tailwind value
- [x] 4. Update `views/dashboard/index.php` to use semantic grid class instead of arbitrary Tailwind value
- [x] 5. Add cache-busting to `asset()` helper in `src/helpers.php`
- [x] 6. Verify no `.gitignore` / `.dockerignore` changes needed for jsQR


