#!/bin/bash

################################################################################
# Production Cleanup & Backup Script
# Gym Attendance Checker — Safe removal of dev/demo files
#
# SAFETY FEATURES:
#  - Creates timestamped backup before deletion
#  - Validates backup integrity (verifies tar file)
#  - Dry-run mode for testing (use: DRYRUN=1 ./script.sh)
#  - Rollback function if needed
#  - Detailed logging of all operations
#
# USAGE:
#  ./production-cleanup.sh              # Normal mode (creates backup, deletes files)
#  DRYRUN=1 ./production-cleanup.sh     # Test mode (shows what would be deleted)
#  ./production-cleanup.sh rollback     # Restore from last backup
#
# REQUIREMENTS:
#  - bash 4+
#  - tar, gzip
#  - ~50 MB free disk space (for backup)
#  - Run from project root directory
#
################################################################################

set -o pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="${SCRIPT_DIR}/.backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/gym-attendance-before-cleanup_${TIMESTAMP}.tar.gz"
LOG_FILE="${SCRIPT_DIR}/cleanup_${TIMESTAMP}.log"
DRYRUN="${DRYRUN:-0}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

################################################################################
# Functions
################################################################################

log() {
    local level=$1
    shift
    local msg="$@"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')

    case $level in
        INFO)  echo -e "${BLUE}[INFO]${NC} ${msg}" | tee -a "$LOG_FILE" ;;
        WARN)  echo -e "${YELLOW}[WARN]${NC} ${msg}" | tee -a "$LOG_FILE" ;;
        ERROR) echo -e "${RED}[ERROR]${NC} ${msg}" | tee -a "$LOG_FILE" ;;
        OK)    echo -e "${GREEN}[OK]${NC} ${msg}" | tee -a "$LOG_FILE" ;;
    esac
}

fail() {
    log ERROR "$@"
    exit 1
}

# Validate we're in the project root
validate_root() {
    if [[ ! -f "${SCRIPT_DIR}/composer.json" ]]; then
        fail "Must run from project root (where composer.json exists)"
    fi
    log OK "Running from project root: ${SCRIPT_DIR}"
}

# Create backup directory
prepare_backup_dir() {
    mkdir -p "$BACKUP_DIR" || fail "Cannot create backup directory: ${BACKUP_DIR}"
    log OK "Backup directory ready: ${BACKUP_DIR}"
}

# Create backup tarball
create_backup() {
    log INFO "Creating backup of entire project..."
    log INFO "This may take 1-2 minutes..."

    # Create tar, excluding backup directory itself
    if tar --exclude="${BACKUP_DIR}" \
           --exclude="./.git/objects/pack/*.keep" \
           -czf "$BACKUP_FILE" \
           -C "$(dirname "$SCRIPT_DIR")" \
           "$(basename "$SCRIPT_DIR")" 2>> "$LOG_FILE"; then

        local size_mb=$(($(stat -f%z "$BACKUP_FILE" 2>/dev/null || stat -c%s "$BACKUP_FILE" 2>/dev/null) / 1024 / 1024))
        log OK "Backup created: ${BACKUP_FILE} (${size_mb} MB)"
    else
        fail "Failed to create backup. See ${LOG_FILE}"
    fi
}

# Verify backup integrity
verify_backup() {
    log INFO "Verifying backup integrity..."

    if tar -tzf "$BACKUP_FILE" > /dev/null 2>&1; then
        log OK "Backup verified successfully"
    else
        fail "Backup verification failed! Not proceeding with deletion."
    fi
}

# List files to be deleted
list_deletions() {
    log INFO "Files/folders to be deleted:"
    log INFO ""

    local items=(
        "vendor/"
        ".git/"
        "docker/"
        "docker-compose.yml"
        "Dockerfile"
        "k8s/"
        "cloudflare-worker.js"
        "wrangler.toml"
        "deploy.sh"
        "demo-deploy.sh"
        "demo-deploy.ps1"
        "quick-demo.ps1"
        "CLOUDFLARE_DEPLOYMENT.md"
        "CLIENT_DEPLOYMENT_SUMMARY.md"
        "COMPREHENSIVE_TEST_REPORT.md"
        "FINAL_VERIFICATION_REPORT.md"
        "IMPLEMENTATION_SUMMARY.md"
        "MEMBER_CODE_CHANGES.INDEX.md"
        "MEMBER_CODE_MIGRATION.md"
        "MIGRATION_QUICK_START.md"
        "QUICK_DEMO.md"
        "TODO.md"
        ".env.docker"
        ".env.docker.example"
        ".dockerignore"
        ".gitignore"
        "test_smtp.php"
        "public/uploads/checkin_photos/*"
    )

    for item in "${items[@]}"; do
        if [[ -e "${SCRIPT_DIR}/${item}" ]]; then
            local size=$(du -sh "${SCRIPT_DIR}/${item}" 2>/dev/null | awk '{print $1}')
            log INFO "  - ${item} (${size})"
        fi
    done
    log INFO ""
}

# Perform actual deletion
perform_deletion() {
    local deleted=0
    local failed=0

    log INFO "Removing files..."

    local items=(
        "vendor"
        ".git"
        "docker"
        "docker-compose.yml"
        "Dockerfile"
        "k8s"
        "cloudflare-worker.js"
        "wrangler.toml"
        "deploy.sh"
        "demo-deploy.sh"
        "demo-deploy.ps1"
        "quick-demo.ps1"
        "CLOUDFLARE_DEPLOYMENT.md"
        "CLIENT_DEPLOYMENT_SUMMARY.md"
        "COMPREHENSIVE_TEST_REPORT.md"
        "FINAL_VERIFICATION_REPORT.md"
        "IMPLEMENTATION_SUMMARY.md"
        "MEMBER_CODE_CHANGES.INDEX.md"
        "MEMBER_CODE_MIGRATION.md"
        "MIGRATION_QUICK_START.md"
        "QUICK_DEMO.md"
        "TODO.md"
        ".env.docker"
        ".env.docker.example"
        ".dockerignore"
        ".gitignore"
        "test_smtp.php"
    )

    for item in "${items[@]}"; do
        local path="${SCRIPT_DIR}/${item}"
        if [[ -e "$path" ]]; then
            if rm -rf "$path" 2>> "$LOG_FILE"; then
                log OK "  ✓ Deleted: ${item}"
                ((deleted++))
            else
                log ERROR "  ✗ Failed to delete: ${item}"
                ((failed++))
            fi
        fi
    done

    # Remove demo photos (glob pattern)
    if [[ -d "${SCRIPT_DIR}/public/uploads/checkin_photos" ]]; then
        if find "${SCRIPT_DIR}/public/uploads/checkin_photos" -type f ! -name ".htaccess" ! -name ".gitkeep" -delete 2>> "$LOG_FILE"; then
            log OK "  ✓ Cleared demo photos"
            ((deleted++))
        fi
    fi

    log INFO ""
    log OK "Deletion complete: ${deleted} items removed, ${failed} failures"

    if [[ $failed -gt 0 ]]; then
        fail "Some deletions failed. Check ${LOG_FILE}"
    fi
}

# Composer cleanup
run_composer_cleanup() {
    log INFO "Optimizing dependencies with Composer..."

    if command -v composer &> /dev/null; then
        if composer install --no-dev --optimize-autoloader 2>> "$LOG_FILE"; then
            log OK "Composer dependencies optimized for production"
        else
            log WARN "Composer install failed. You may need to run manually: composer install --no-dev"
        fi
    else
        log WARN "Composer not found in PATH. You'll need to run manually:"
        log WARN "  composer install --no-dev --optimize-autoloader"
    fi
}

# Disk space report
report_space() {
    log INFO ""
    log INFO "Disk space report:"

    local total=$(du -sh "$SCRIPT_DIR" 2>/dev/null | awk '{print $1}')
    log INFO "  Project directory: ${total}"

    # Show largest directories
    log INFO ""
    log INFO "Largest directories (top 5):"
    du -sh "$SCRIPT_DIR"/* 2>/dev/null | sort -h | tail -5 | while read line; do
        log INFO "    ${line}"
    done
}

# Rollback from backup
rollback() {
    log WARN "Rollback requested"

    # Find most recent backup
    local latest=$(ls -t "${BACKUP_DIR}"/gym-attendance-before-cleanup_*.tar.gz 2>/dev/null | head -1)

    if [[ -z "$latest" ]]; then
        fail "No backup files found in ${BACKUP_DIR}"
    fi

    log WARN "Restoring from: ${latest}"
    log WARN "This will overwrite current files..."

    read -p "Type 'YES' to confirm rollback: " confirm
    if [[ "$confirm" != "YES" ]]; then
        log INFO "Rollback cancelled"
        return
    fi

    # Extract backup (creates a new copy, doesn't overwrite in-place)
    cd "$(dirname "$SCRIPT_DIR")" || fail "Cannot cd to parent directory"

    if tar -xzf "$latest"; then
        log OK "Rollback completed. Files restored from backup."
        log INFO "Backup file: ${latest}"
    else
        fail "Rollback failed. See ${LOG_FILE}"
    fi
}

# Main flow for dry-run
dry_run() {
    log WARN "DRY-RUN MODE (no changes will be made)"
    log INFO ""

    validate_root
    list_deletions

    log INFO ""
    log WARN "To actually perform these deletions, run:"
    log WARN "  ./production-cleanup.sh"
    log INFO ""
}

# Main flow for actual run
main_run() {
    log INFO "========================================="
    log INFO "Production Cleanup Script"
    log INFO "Project: ${SCRIPT_DIR}"
    log INFO "========================================="
    log INFO ""

    validate_root
    prepare_backup_dir
    list_deletions

    log WARN ""
    log WARN "IMPORTANT: Review the files above before continuing."
    log WARN ""
    read -p "Continue with backup and deletion? (yes/no): " confirm

    if [[ "$confirm" != "yes" && "$confirm" != "y" ]]; then
        log WARN "Cancelled by user"
        exit 0
    fi

    log INFO ""
    create_backup
    verify_backup

    log INFO ""
    perform_deletion

    log INFO ""
    run_composer_cleanup

    log INFO ""
    report_space

    log INFO ""
    log OK "========================================="
    log OK "Cleanup complete!"
    log OK "========================================="
    log OK "Backup saved: ${BACKUP_FILE}"
    log OK "Log saved: ${LOG_FILE}"
    log OK ""
    log OK "Next steps:"
    log OK "  1. Test the application thoroughly"
    log OK "  2. Run Part E verification steps"
    log OK "  3. Deploy to Hostinger"
    log OK ""
}

################################################################################
# Entry point
################################################################################

# Initialize log file
touch "$LOG_FILE"
log INFO "Script started with DRYRUN=${DRYRUN}"

if [[ "$1" == "rollback" ]]; then
    rollback
elif [[ $DRYRUN -eq 1 ]]; then
    dry_run
else
    main_run
fi

log INFO "Script completed successfully"
exit 0
