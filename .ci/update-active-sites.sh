#!/usr/bin/env bash

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
stack_root="$(cd "${script_dir}/../../../../../.." && pwd)"
workspace_root="$(cd "${stack_root}/.." && pwd)"

package_name="industrialdev/wicket-lib-org-roster"
composer_subdir="src"

declare -a active_sites=(
  "cchl-website-wordpress"
  "escrs-website-wordpress"
  "iaa-website-wordpress"
  "msa-website-wordpress"
  "njbia-website-wordpress"
)

declare -a requested_sites=()
dry_run=0

usage() {
  cat <<'EOF'
Usage:
  .ci/update-active-sites.sh [--dry-run] [site-name ...]

Examples:
  .ci/update-active-sites.sh
  .ci/update-active-sites.sh --dry-run
  .ci/update-active-sites.sh msa-website-wordpress njbia-website-wordpress
EOF
}

contains_site() {
  local needle="$1"
  shift
  local item
  for item in "$@"; do
    if [[ "${item}" == "${needle}" ]]; then
      return 0
    fi
  done

  return 1
}

while (($# > 0)); do
  case "$1" in
    --dry-run)
      dry_run=1
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      requested_sites+=("$1")
      ;;
  esac
  shift
done

if ((${#requested_sites[@]} > 0)); then
  for site in "${requested_sites[@]}"; do
    if ! contains_site "${site}" "${active_sites[@]}"; then
      echo "[orgman-update] Unknown site: ${site}" >&2
      echo "[orgman-update] Allowed sites: ${active_sites[*]}" >&2
      exit 1
    fi
  done
else
  requested_sites=("${active_sites[@]}")
fi

echo "[orgman-update] Workspace root: ${workspace_root}"
echo "[orgman-update] Package: ${package_name}"
if ((dry_run)); then
  echo "[orgman-update] Dry run enabled"
fi

updated_count=0

for site in "${requested_sites[@]}"; do
  site_root="${workspace_root}/${site}"
  composer_root="${site_root}/${composer_subdir}"
  composer_file="${composer_root}/composer.json"

  echo
  echo "[orgman-update] Site: ${site}"

  if [[ ! -d "${composer_root}" ]]; then
    echo "[orgman-update] Missing composer root: ${composer_root}" >&2
    exit 1
  fi

  if [[ ! -f "${composer_file}" ]]; then
    echo "[orgman-update] Missing composer.json: ${composer_file}" >&2
    exit 1
  fi

  if ! rg -q "\"${package_name}\"" "${composer_file}"; then
    echo "[orgman-update] ${package_name} not required in ${composer_file}; skipping"
    continue
  fi

  echo "[orgman-update] Composer root: ${composer_root}"

  if ((dry_run)); then
    echo "[orgman-update] Would run: composer update ${package_name}"
    updated_count=$((updated_count + 1))
    continue
  fi

  (
    cd "${composer_root}"
    composer update "${package_name}"
  )

  updated_count=$((updated_count + 1))
  echo "[orgman-update] Updated ${site}"
done

echo
echo "[orgman-update] Complete. Processed ${updated_count} site(s)."
