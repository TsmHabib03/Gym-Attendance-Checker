# TODO: Fix 403 Forbidden on /public

## Problem
Accessing `http://localhost/gym-attendance-checker/public` returns **403 Forbidden** because:
1. Root `.htaccess` (`gym-attendance-checker/.htaccess`) has `Require all denied` (defense-in-depth for misconfigured docroots).
2. `public/.htaccess` does **not** explicitly grant access, so the parent's denial cascades.

## Plan
1. **Edit `public/.htaccess`**:
   - Add an explicit `Require all granted` / `Allow from all` block at the top to override the root `.htaccess` denial **only** for the `public/` directory.
   - Move `DirectoryIndex index.php` outside the `<IfModule !mod_rewrite.c>` fallback block so it always applies.
   - Keep all existing security rules intact (probes, traversal, TRACE, dotfiles, backup files, headers, upload limits).
2. **Verify** root directory and sensitive paths (`src/`, `views/`, `storage/`, `.env`) still return 403.
3. **Test** `http://localhost/gym-attendance-checker/public` loads correctly.

